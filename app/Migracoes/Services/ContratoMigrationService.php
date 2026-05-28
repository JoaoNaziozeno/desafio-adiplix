<?php

namespace App\Migracoes\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Migracoes\Payloads\ContratoPayload;

class ContratoMigrationService
{
    private PessoaMigrationService $pessoaService;

    public function __construct(PessoaMigrationService $pessoaService)
    {
        $this->pessoaService = $pessoaService;
    }

    public function migrar(array $mapaPlanos): void
    {
        $contratosOrigem = DB::connection('sistema_proprio')->table('contrato')->get();

        foreach ($contratosOrigem as $contrato) {
            $pessoaOrigem = DB::connection('sistema_proprio')
                ->table('pessoa')
                ->where('id', $contrato->id_pessoa)
                ->first();

            if (!$pessoaOrigem) continue;

            // Regra 3.5: Sempre solicita a criação de uma nova linha de pessoa
            // garantindo a relação estrita de 1:1 por contrato no destino.
            $idPessoaDestino = $this->pessoaService->migrarPessoa($pessoaOrigem);

            // Regra 3.6: Status
            $situacao = 'Carência';
            $dataCancelamento = null;
            if (!empty($contrato->data_cancelamento)) {
                $situacao = 'Cancelado';
                $dataCancelamento = $contrato->data_cancelamento;
            }

            // Regra 3.2: Busca ID do plano migrado
            $chavePlanoBuscado = Str::slug($contrato->plano) . '_' . (float)$contrato->valor;
            $idPlanoDestino = $mapaPlanos[$chavePlanoBuscado] ?? 1;

            $dadosContrato = ContratoPayload::criar([
                'Id'             => $contrato->id,
                'Matricula'      => (string)$contrato->id,
                'IdPessoa'       => $idPessoaDestino,
                'IdPlano'        => $idPlanoDestino,
                'Vendedor'       => 0,
                'Cobrador'       => 0,
                'FormaPagamento' => $this->buscarFormaPagamento($contrato->forma_pagamento),
                'Situacao'       => $situacao,
                'Cancelamento'   => $dataCancelamento,
                'externo_id'     => (string)$contrato->id
            ]);

            DB::table('nando690_exclusivesis_sistema_proprio.contrato')->insert($dadosContrato);
        }
    }

    private function buscarFormaPagamento($formaOrigem)
    {
        $dePara = DB::table('nando690_exclusivesis_sistema_proprio.contrato_formapagamento')
            ->where('descricao', 'LIKE', "%{$formaOrigem}%")
            ->first();
        return $dePara ? $dePara->id : 1;
    }
}