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
            
            // Primeiro, vamos tentar buscar campanhas sem relacionamentos para verificar se a tabela existe
            $campaignsBasic = Campaign::all();
            \Log::info('Campaign index - Campanhas básicas encontradas: ' . $campaignsBasic->count());
            
            // Agora vamos tentar com os relacionamentos
            $campaigns = Campaign::with(['contactList' => function ($query) {
                $query->select('contact_list.id', 'contact_list.contact_id')
                    ->withPivot('send');
            }])->get();
            
            \Log::info('Campaign index - Campanhas com relacionamentos: ' . $campaigns->count());
            
            // Mapear os dados
            $campaigns = $campaigns->map(function ($campaign) {
                try {
                    $campaign->total_to_send = $campaign->contactList ? $campaign->contactList->count() : 0;
                    $campaign->total_sent = $campaign->contactList ? $campaign->contactList->where('pivot.send', true)->count() : 0;
                    $campaign->total_not_sent = $campaign->contactList ? $campaign->contactList->where('pivot.send', false)->count() : 0;
                    return $campaign;
                } catch (\Exception $e) {
                    \Log::error('Erro ao processar campanha ID ' . $campaign->id . ': ' . $e->getMessage());
                    $campaign->total_to_send = 0;
                    $campaign->total_sent = 0;
                    $campaign->total_not_sent = 0;
                    return $campaign;
                }
            });

            \Log::info('Campaign index - Campanhas processadas com sucesso');
            
            return view('sistema.campaign.index', compact('campaigns'));
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Erro de banco de dados no Campaign index: ' . $e->getMessage());
            
            // Se houver erro de banco, retorna campanhas básicas sem relacionamentos
            try {
                $campaigns = Campaign::all()->map(function ($campaign) {
                    $campaign->total_to_send = 0;
                    $campaign->total_sent = 0;
                    $campaign->total_not_sent = 0;
                    return $campaign;
                });
                
                return view('sistema.campaign.index', compact('campaigns'));
            } catch (\Exception $fallbackError) {
                \Log::error('Erro no fallback: ' . $fallbackError->getMessage());
                return back()->withErrors(['error' => 'Erro ao carregar campanhas. Verifique as tabelas do banco de dados.']);
            }
            
        } catch (\Exception $e) {
            \Log::error('Erro geral no Campaign index: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return back()->withErrors(['error' => 'Erro ao carregar campanhas: ' . $e->getMessage()]);
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
