<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AvailableSlot;
use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Contact;
use App\Models\ContactList;
use App\Models\ImagemEmMassa;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use SplFileObject;

class CampaignController extends Controller
{
    public function index()
    {
        try {
            \Log::info('Campaign index - Iniciando busca de campanhas');
            
            // Versão simplificada que funciona sem relacionamentos problemáticos
            \Log::info('Campaign index - Usando versão simplificada sem relacionamentos');
            
            // Query manual que inclui os dados necessários para a view
            $campaigns = \DB::table('campaigns')
                ->leftJoin('campaign_contact', 'campaigns.id', '=', 'campaign_contact.campaign_id')
                ->leftJoin('contact_list', 'campaign_contact.contact_list_id', '=', 'contact_list.id')
                ->leftJoin('imagem_em_massa', 'campaigns.imagem_id', '=', 'imagem_em_massa.id')
                ->leftJoin('contacts', 'campaigns.contact_id', '=', 'contacts.id')
                ->select(
                    'campaigns.*', 
                    'imagem_em_massa.caminho as imagem_caminho',
                    'contacts.name as contact_name',
                    \DB::raw('COUNT(contact_list.id) as total_contacts'),
                    \DB::raw('SUM(CASE WHEN campaign_contact.send = 1 THEN 1 ELSE 0 END) as total_sent')
                )
                ->groupBy(
                    'campaigns.id', 'campaigns.titulo', 'campaigns.texto', 'campaigns.contact_id', 
                    'campaigns.imagem_id', 'campaigns.status', 'campaigns.created_at', 'campaigns.updated_at',
                    'imagem_em_massa.caminho', 'contacts.name'
                )
                ->get();
            
            \Log::info('Campaign index - Query manual executada. Total campanhas: ' . $campaigns->count());
            
            $campaigns = $campaigns->map(function ($campaign) {
                // Adicionar propriedades que a view espera
                $campaign->total_to_send = $campaign->total_contacts ?? 0;
                $campaign->total_sent = $campaign->total_sent ?? 0;
                $campaign->total_not_sent = $campaign->total_to_send - $campaign->total_sent;
                
                // Sempre criar objeto imagem (mesmo que vazio)
                // Se existe caminho, usa o caminho salvo no banco, senão usa padrão
                $imagemCaminho = $campaign->imagem_caminho 
                    ? $campaign->imagem_caminho 
                    : 'assets/images/default-campaign.png';
                
                $campaign->imagem = (object) [
                    'caminho' => $imagemCaminho
                ];
                
                // Sempre criar objeto contact (mesmo que vazio)
                $campaign->contact = (object) [
                    'name' => $campaign->contact_name ?? 'Sem contato'
                ];
                
                // Log para debug
                \Log::info('Campaign ' . $campaign->id . ' - Imagem original: ' . ($campaign->imagem_caminho ?? 'null') . ' - Caminho final: ' . $imagemCaminho);
                
                return $campaign;
            });
            
            \Log::info('Campaign index - Campanhas processadas com sucesso');
            \Log::info('Campaign index - Tentando carregar a view...');
            
            return view('sistema.campaign.index', compact('campaigns'));
            
        } catch (\Exception $e) {
            \Log::error('Erro geral no Campaign index: ' . $e->getMessage());
            \Log::error('Arquivo: ' . $e->getFile() . ' Linha: ' . $e->getLine());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Fallback final: campanhas básicas
            try {
                $campaigns = \DB::table('campaigns')->get()->map(function ($campaign) {
                    $campaign->total_to_send = 0;
                    $campaign->total_sent = 0;
                    $campaign->total_not_sent = 0;
                    $campaign->imagem = (object) [
                        'nome' => '',
                        'caminho' => '/assets/images/default-campaign.png'
                    ];
                    $campaign->contact = (object) [
                        'name' => 'Sem contato'
                    ];
                    return $campaign;
                });
                
                return view('sistema.campaign.index', compact('campaigns'));
                
            } catch (\Exception $fallbackError) {
                \Log::error('Erro no fallback: ' . $fallbackError->getMessage());
                return response()->json(['error' => 'Erro ao carregar campanhas'], 500);
            }
        }
    }

    public function deleteCampanha($id)
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->delete();

        return redirect()->route('campaign.index')->with('success', 'Campanha excluída com sucesso.');
    }

    public function updateStatus(Request $request)
    {
        $campaign = Campaign::find($request->id);
        if ($campaign) {
            $campaign->status = $campaign->status == 'play' ? 'pause' : 'play';
            $campaign->save();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false]);
    }

    public function show($id)
    {
        $campaign = Campaign::with(['contactList.contact'])->findOrFail($id);
        return view('sistema.campaign.show', compact('campaign'));
    }
    public function destroyContact($campaignId, $contactListId)
    {

        $campaignContact = CampaignContact::where('campaign_id', $campaignId)
            ->where('contact_list_id', $contactListId)
            ->firstOrFail();
        $campaignContact->delete();

        return redirect()->route('sistema.campaign.show', $campaignId)->with('success', 'Contato excluído com sucesso.');
    }
    public function edit($id)
    {
        $campaign = Campaign::find($id);
        if (!$campaign) {
            return redirect()->route('campaign.index')->with('error', 'Campanha não encontrada.');
        }
        $imagens = ImagemEmMassa::all();

        return view('sistema.campaign.edit', compact('imagens', 'campaign'));
    }
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'titulo' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }



        if (!isset($request->imagem_id)) {
            return redirect()->back()->with('error', 'Seleciono uma Imagens')->withInput();
        }

        // Encontrar a campanha pelo ID
        $campaign = Campaign::find($id);
        if (!$campaign) {
            return redirect()->route('campaign.index')->with('error', 'Campanha não encontrada.');
        }

        // Atualizar os campos da campanha
        $campaign->titulo = $request->titulo;
        $campaign->texto = $request->texto;
        $campaign->imagem_id = $request->imagem_id;
        $campaign->save();

        return redirect()->route('campaign.index')->with('success', 'Campanha atualizada com sucesso.');
    }
}
