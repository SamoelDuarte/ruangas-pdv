Análise do problema da rota /campanha/relatorio-de-envio:

## Possíveis causas do erro 500:

1. **Problema com relacionamentos**: O relacionamento `contactList` pode não estar funcionando corretamente
2. **Tabela pivot ausente**: A tabela `campaign_contact` pode não existir
3. **Colunas ausentes**: As colunas `send` na tabela pivot podem não existir
4. **Problema na view**: A view `sistema.campaign.index` pode ter erro

## Para debugar:

### 1. Verificar se as tabelas existem:
```sql
SHOW TABLES LIKE 'campaigns';
SHOW TABLES LIKE 'campaign_contact'; 
SHOW TABLES LIKE 'contact_list';
```

### 2. Verificar estrutura da tabela pivot:
```sql
DESCRIBE campaign_contact;
```

### 3. Testar a rota com logs:
- Os logs agora foram adicionados ao controller
- Verifique em `storage/logs/laravel.log` 

### 4. Rota de teste para verificar campanhas:
Adicione esta rota temporária no web.php:

```php
Route::get('/debug-campaigns', function() {
    try {
        $campaigns = \App\Models\Campaign::all();
        return response()->json([
            'status' => 'success',
            'campaigns_count' => $campaigns->count(),
            'campaigns' => $campaigns->toArray()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
});
```

### 5. Verificar se a view existe:
```bash
ls resources/views/sistema/campaign/index.blade.php
```