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
            
            // Agora vamos tentar com os relacionamentos - TESTANDO MAIS SIMPLES PRIMEIRO
            \Log::info('Campaign index - Tentando carregar relacionamentos...');
            
            try {
                // Teste 1: Relacionamento simples sem select customizado
                \Log::info('Campaign index - Teste 1: Relacionamento simples');
                $campaigns = Campaign::with(['contactList'])->get();
                \Log::info('Campaign index - Teste 1 bem-sucedido. Total: ' . $campaigns->count());
                
            } catch (\Exception $test1Error) {
                \Log::error('Campaign index - Teste 1 falhou: ' . $test1Error->getMessage());
                
                try {
                    // Teste 2: Verificar se a tabela pivot existe
                    \Log::info('Campaign index - Teste 2: Verificando tabela pivot');
                    $pivotTest = \DB::table('campaign_contact')->limit(1)->get();
                    \Log::info('Campaign index - Tabela pivot existe e é acessível');
                    
                    // Teste 3: Relacionamento sem pivot customizado
                    \Log::info('Campaign index - Teste 3: Relacionamento sem withPivot');
                    $campaigns = Campaign::with(['contactList' => function ($query) {
                        $query->select('contact_list.id', 'contact_list.contact_id');
                    }])->get();
                    \Log::info('Campaign index - Teste 3 bem-sucedido');
                    
                } catch (\Exception $test2Error) {
                    \Log::error('Campaign index - Teste 2/3 falhou: ' . $test2Error->getMessage());
                    
                    // Fallback final: campanhas sem relacionamentos
                    \Log::info('Campaign index - Usando fallback sem relacionamentos');
                    $campaigns = $campaignsBasic->map(function ($campaign) {
                        $campaign->total_to_send = 0;
                        $campaign->total_sent = 0;
                        $campaign->total_not_sent = 0;
                        return $campaign;
                    });
                    
                    return view('sistema.campaign.index', compact('campaigns'));
                }
            }
            
            // Mapear os dados de forma mais segura
            \Log::info('Campaign index - Iniciando processamento dos dados...');
            
            $campaigns = $campaigns->map(function ($campaign) {
                try {
                    \Log::info('Campaign index - Processando campanha ID: ' . $campaign->id);
                    
                    // Verificar se contactList existe e é uma collection
                    if ($campaign->relationLoaded('contactList') && $campaign->contactList) {
                        $campaign->total_to_send = $campaign->contactList->count();
                        
                        // Verificar se o pivot tem a coluna 'send'
                        $firstContact = $campaign->contactList->first();
                        if ($firstContact && isset($firstContact->pivot->send)) {
                            $campaign->total_sent = $campaign->contactList->where('pivot.send', true)->count();
                            $campaign->total_not_sent = $campaign->contactList->where('pivot.send', false)->count();
                        } else {
                            \Log::warning('Campaign index - Pivot send não encontrado para campanha ' . $campaign->id);
                            $campaign->total_sent = 0;
                            $campaign->total_not_sent = $campaign->total_to_send;
                        }
                    } else {
                        \Log::warning('Campaign index - ContactList não carregado para campanha ' . $campaign->id);
                        $campaign->total_to_send = 0;
                        $campaign->total_sent = 0;
                        $campaign->total_not_sent = 0;
                    }
                    
                    \Log::info('Campaign index - Campanha ' . $campaign->id . ' processada. To send: ' . $campaign->total_to_send);
                    
                    return $campaign;
                } catch (\Exception $e) {
                    \Log::error('Erro ao processar campanha ID ' . $campaign->id . ': ' . $e->getMessage());
                    $campaign->total_to_send = 0;
                    $campaign->total_sent = 0;
                    $campaign->total_not_sent = 0;
                    return $campaign;
                }
            });

            \Log::info('Campaign index - Todas as campanhas processadas com sucesso');
            \Log::info('Campaign index - Tentando carregar a view...');
            
            return view('sistema.campaign.index', compact('campaigns'));
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Erro de banco de dados no Campaign index: ' . $e->getMessage());
            \Log::error('SQL: ' . $e->getSql());
            \Log::error('Bindings: ' . json_encode($e->getBindings()));
            
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
            \Log::error('Arquivo: ' . $e->getFile() . ' Linha: ' . $e->getLine());
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
