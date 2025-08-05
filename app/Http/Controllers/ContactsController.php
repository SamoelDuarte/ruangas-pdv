<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Maatwebsite\Excel\Facades\Excel;
use SplFileObject;

class ContactsController extends Controller
{
    public function index()
    {
        $contacts = Contact::withCount('contactLists')->get();
        return view('sistema.contact.index', compact('contacts'));
    }

    public function show($id)
    {
        $contact = Contact::with('contactLists')->findOrFail($id);
        return view('sistema.contact.show', compact('contact'));
    }

    public function destroy($id)
    {
        $contactList = ContactList::findOrFail($id);
        $contactList->delete();

        return back()->with('success', 'Contato deletado com sucesso');
    }

    public function storeContact(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:15',
            'contact_id' => 'required|integer|exists:contacts,id',
        ]);

        $phoneFormater = $this->formatarTexto($request->phone);

        if ($phoneFormater) {
            $exists = ContactList::where('phone', $phoneFormater)
                ->where('contact_id', $request->contact_id)
                ->exists();

            if (!$exists) {
                ContactList::create([
                    'phone' => $phoneFormater,
                    'contact_id' => $request->contact_id,
                ]);
            }

            return back()->with('success', 'Contato adicionado com sucesso');
        }

        return back()->with('error', 'Número de telefone inválido');
    }

    public function storeFile(Request $request)
    {
        $request->validate([
            'csvFile' => 'required|file|mimes:csv,xlsx,xls|max:2048',
            'contact_id' => 'required|exists:contacts,id'
        ]);

        $file = $request->file('csvFile');
        $contact = Contact::findOrFail($request->contact_id);
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'csv') {
            $this->processarCSV($file, $contact);
            return response()->json(['message' => 'Contatos CSV salvos com sucesso']);
        }

        if (in_array($extension, ['xlsx', 'xls'])) {
            $this->processarExcel($file, $contact);
            return response()->json(['message' => 'Contatos Excel salvos com sucesso']);
        }

        return response()->json(['message' => 'Formato de arquivo não suportado'], 400);
    }

    public function store(Request $request)
    {
        if (empty($request->name)) {
            return back()->with('error', 'Mensagem não pode estar vazia');
        }

        if (!$request->hasFile('csvFile')) {
            return back()->with('error', 'Escolha um arquivo CSV');
        }

        $contact = new Contact();
        $contact->name = $request->name;
        $contact->save();

        $file = $request->file('csvFile');
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'csv') {
            $this->processarCSV($file, $contact);
        } elseif (in_array($extension, ['xlsx', 'xls'])) {
            $this->processarExcel($file, $contact);
        } else {
            return back()->with('error', 'Formato de arquivo não suportado');
        }

        return Redirect::route('contact.index')->with('success', 'Lista de contatos salva com sucesso');
    }

    private function processarCSV($file, $contact)
    {
        $handle = new SplFileObject($file->getPathname(), 'r');
        $handle->setFlags(SplFileObject::READ_CSV);

        // Não pular nenhuma linha, processa do começo ao fim
        while (!$handle->eof()) {
            $linha = $handle->fgetcsv();
            $this->salvarContatoDaLinha($linha, $contact);
        }
    }

    private function processarExcel($file, $contact)
    {
        $imported = Excel::toArray([], $file);
        $rows = $imported[0] ?? [];

        // Processa todas as linhas, sem pular nenhuma
        foreach ($rows as $linha) {
            $this->salvarContatoDaLinha($linha, $contact);
        }
    }


    private function salvarContatoDaLinha(array $linha, Contact $contact)
    {
        if (!empty($linha[0])) {
            $phone = $linha[0];
            $phoneFormater = $this->formatarTexto($phone);
            if ($phoneFormater) {
                $exists = ContactList::where('phone', $phoneFormater)
                    ->where('contact_id', $contact->id)
                    ->exists();
                if (!$exists) {
                    ContactList::create([
                        'phone' => $phoneFormater,
                        'contact_id' => $contact->id,
                    ]);
                }
            }
        }
    }


    public function formatarTexto($texto)
{
    // Remove tudo que não for número
    $soNumeros = preg_replace('/\D/', '', $texto);

    // Remove o código do país se existir (com ou sem +)
    $soNumeros = preg_replace('/^55/', '', $soNumeros);

    // Se tiver exatamente 11 dígitos (DDD + número), é válido
    if (strlen($soNumeros) === 11) {
        return '55' . $soNumeros;
    }

    // Número inválido
    return false;
}


    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $contact = Contact::findOrFail($id);
        $contact->name = $request->name;
        $contact->save();

        return response()->json(['success' => true]);
    }

    public function delete(Request $request)
    {
        $contact = Contact::findOrFail($request->id);
        $contact->delete();

        return response()->json(['success' => true]);
    }
}
