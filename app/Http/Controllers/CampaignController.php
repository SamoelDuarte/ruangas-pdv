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
            
            // Agora vamos testar cada componente separadamente
            \Log::info('Campaign index - Tentando carregar relacionamentos...');
            
            try {
                // Teste A: Verificar se ContactList model funciona
                \Log::info('Campaign index - Teste A: Verificando modelo ContactList');
                $contactListTest = \App\Models\ContactList::limit(1)->get();
                \Log::info('Campaign index - Teste A bem-sucedido. ContactList acessível');
                
                // Teste B: Verificar tabela pivot campaign_contact
                \Log::info('Campaign index - Teste B: Verificando tabela pivot campaign_contact');
                $pivotData = \DB::table('campaign_contact')->limit(1)->get();
                \Log::info('Campaign index - Teste B bem-sucedido. Tabela pivot acessível');
                
                // Teste C: Relacionamento básico SEM eager loading
                \Log::info('Campaign index - Teste C: Testando relacionamento sem eager loading');
                $firstCampaign = Campaign::first();
                if ($firstCampaign) {
                    $contactListRelation = $firstCampaign->contactList()->limit(1)->get();
                    \Log::info('Campaign index - Teste C bem-sucedido. Relacionamento funciona');
                }
                
                // Teste D: Eager loading simples
                \Log::info('Campaign index - Teste D: Eager loading simples');
                
                try {
                    // D1: Eager loading sem array
                    \Log::info('Campaign index - Teste D1: Eager loading string simples');
                    $campaigns = Campaign::with('contactList')->get();
                    \Log::info('Campaign index - Teste D1 bem-sucedido. Total: ' . $campaigns->count());
                    
                } catch (\Exception $d1Error) {
                    \Log::error('Campaign index - Teste D1 falhou: ' . $d1Error->getMessage());
                    \Log::error('Campaign index - Arquivo D1: ' . $d1Error->getFile() . ' Linha: ' . $d1Error->getLine());
                    \Log::error('Campaign index - Stack trace D1: ' . $d1Error->getTraceAsString());
                    
                    try {
                        // D2: Verificar se o problema é no withPivot - usar query manual
                        \Log::info('Campaign index - Teste D2: Usando query manual para contornar o problema');
                        
                        // Query manual que funciona independente do relacionamento Eloquent
                        $campaigns = \DB::table('campaigns')
                            ->leftJoin('campaign_contact', 'campaigns.id', '=', 'campaign_contact.campaign_id')
                            ->leftJoin('contact_list', 'campaign_contact.contact_list_id', '=', 'contact_list.id')
                            ->select('campaigns.*', \DB::raw('COUNT(contact_list.id) as total_contacts'))
                            ->groupBy('campaigns.id', 'campaigns.titulo', 'campaigns.texto', 'campaigns.contact_id', 'campaigns.imagem_id', 'campaigns.status', 'campaigns.created_at', 'campaigns.updated_at')
                            ->get();
                        
                        \Log::info('Campaign index - Query manual executada. Total campanhas: ' . $campaigns->count());
                        
                        $campaigns = $campaigns->map(function ($campaign) {
                            $campaign->total_to_send = $campaign->total_contacts ?? 0;
                            $campaign->total_sent = 0;
                            $campaign->total_not_sent = $campaign->total_to_send;
                            return $campaign;
                        });
                        
                        \Log::info('Campaign index - Teste D2 bem-sucedido com query manual');
                        \Log::info('Campaign index - Tentando carregar a view...');
                        
                        return view('sistema.campaign.index', compact('campaigns'));
                        
                    } catch (\Exception $d2Error) {
                        \Log::error('Campaign index - Teste D2 também falhou: ' . $d2Error->getMessage());
                        \Log::error('Campaign index - Stack trace D2: ' . $d2Error->getTraceAsString());
                        throw $d2Error;
                    }
                }
                
            } catch (\Exception $testError) {
                \Log::error('Campaign index - Erro nos testes: ' . $testError->getMessage());
                \Log::error('Campaign index - Arquivo: ' . $testError->getFile() . ' Linha: ' . $testError->getLine());
                \Log::error('Campaign index - Stack trace: ' . $testError->getTraceAsString());
                
                // Fallback: campanhas sem relacionamentos
                \Log::info('Campaign index - Usando fallback sem relacionamentos');
                $campaigns = $campaignsBasic->map(function ($campaign) {
                    $campaign->total_to_send = 0;
                    $campaign->total_sent = 0;
                    $campaign->total_not_sent = 0;
                    return $campaign;
                });
                
                return view('sistema.campaign.index', compact('campaigns'));
            }
            
            // Se chegou até aqui, o relacionamento funciona
            \Log::info('Campaign index - Todos os testes passaram. Processando dados...');
            
            $campaigns = $campaigns->map(function ($campaign) {
                try {
                    \Log::info('Campaign index - Processando campanha ID: ' . $campaign->id);
                    
                    if ($campaign->relationLoaded('contactList') && $campaign->contactList) {
                        $campaign->total_to_send = $campaign->contactList->count();
                        $campaign->total_sent = 0;
                        $campaign->total_not_sent = $campaign->total_to_send;
                    } else {
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
