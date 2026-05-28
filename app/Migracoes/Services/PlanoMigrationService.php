<?php

namespace App\Migracoes\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Migracoes\Payloads\PlanoPayload;

class PlanoMigrationService
{
    public function migrar(): array
    {
        $planosOrigem = DB::connection('sistema_proprio')
            ->table('contrato')
            ->select('plano', 'valor')
            ->groupBy('plano', 'valor')
            ->get();

        $mapaPlanos = [];

        foreach ($planosOrigem as $plano) {
            // Alimenta a Factory do Plano
            $arrayPlanoDestino = PlanoPayload::criar([
                'IdFilial'   => 1, 
                'Plano'      => trim($plano->plano),
                'Valor'      => $plano->valor,
                'Data'       => now()->format('Y-m-d'),
                'Usuario'    => 1,
                'externo_id' => trim($plano->plano) . '_' . $plano->valor
            ]);

            // 2. CORREÇÃO: Remove a coluna inexistente que veio do Payload padrão
            unset($arrayPlanoDestino['plano_carteirinha_id']);
            unset($arrayPlanoDestino['limite_parcelas_visiveis_no_app']);

            // 3. Insere no banco de destino limpo
            $idPlanoCriado = DB::table('plano')
                ->insertGetId($arrayPlanoDestino);

            // Cria a chave única na memória para o De-Para
            $chaveMapa = Str::slug($plano->plano) . '_' . (float)$plano->valor;
            $mapaPlanos[$chaveMapa] = $idPlanoCriado;
        }

        return $mapaPlanos;
    }
}