<?php

namespace App\Migracoes\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Migracoes\Payloads\ContratoPayload;

class ContratoMigrationService {

    private PessoaMigrationService $pessoaService;
    private array $inconsistencias = [];

    public function __construct(PessoaMigrationService $pessoaService) {
        $this->pessoaService = $pessoaService;
    }

    public function migrar(array $mapaPlanos): void {
        $contratosOrigem = DB::connection('sistema_proprio')
            ->table('contrato')
            ->get();

        foreach ($contratosOrigem as $contrato) {
            try {
                DB::transaction(function () use ($contrato, $mapaPlanos) {
                    
                    // Normalização das propriedades do contrato (origem pode ser id/Id, id_pessoa/IdPessoa, etc.)
                    $idContratoValido = $contrato->id ?? $contrato->Id ?? null;
                    $idPessoaOrigem   = $contrato->id_pessoa ?? $contrato->IdPessoa ?? $contrato->id_cliente ?? null;
                    $planoOrigem      = $contrato->plano ?? $contrato->Plano ?? '';
                    $valorOrigem      = $contrato->valor ?? $contrato->Valor ?? 0;
                    $dataContratoOrig = $contrato->data_contrato ?? $contrato->DataContrato ?? null;
                    $dataCancelOrig   = $contrato->data_cancelamento ?? $contrato->DataCancelamento ?? null;

                    // BUSCA PESSOA ORIGEM
                    $pessoaOrigem = DB::connection('sistema_proprio')
                        ->table('pessoa')
                        ->where('id', $idPessoaOrigem)
                        ->first();

                    if (!$pessoaOrigem) {
                        throw new Exception("Pessoa origem não encontrada para o ID: " . ($idPessoaOrigem ?? 'Nulo'));
                    }

                    // Prepara e migra pessoa, retornando ID destino
                    $idPessoaDestino = $this->pessoaService->migrarPessoa($pessoaOrigem);

                    if (!$idPessoaDestino) {
                        throw new Exception("Falha ao migrar pessoa vinculada.");
                    }

                    // =========================
                    // REGRA 3.6 - SITUAÇÃO CONTRATO
                    // =========================
                    $situacao = 'Carência';
                    $dataCancelamento = null;

                    if (!empty($dataCancelOrig)) {
                        $situacao = 'Cancelado';
                        $dataCancelamento = $dataCancelOrig;
                    }

                    // =========================
                    // REGRA 3.2 - RELAÇÃO COM PLANO
                    // =========================
                    $chavePlanoBuscado = Str::slug($planoOrigem) . '_' . (float) $valorOrigem;

                    if (!isset($mapaPlanos[$chavePlanoBuscado])) {
                        throw new Exception("Plano '{$planoOrigem}' com valor '{$valorOrigem}' não encontrado no mapa.");
                    }

                    $idPlanoDestino = $mapaPlanos[$chavePlanoBuscado];

                    // =========================
                    // PAYLOAD CONTRATO
                    // =========================
                    $dadosContrato = ContratoPayload::criar([
                        'Id'             => $idContratoValido,
                        'Matricula'      => (string) $idContratoValido,
                        'IdPessoa'       => $idPessoaDestino,
                        'IdPlano'        => $idPlanoDestino,
                        'DataContrato'   => (!empty($dataContratoOrig) && $dataContratoOrig !== '0000-00-00') 
                                            ? date('Y-m-d', strtotime($dataContratoOrig)) 
                                            : now()->format('Y-m-d'),
                        'Vendedor'       => 0,
                        'Cobrador'       => 0,
                        'FormaPagamento' => $this->buscarFormaPagamento($contrato),
                        'Situacao'       => $situacao,
                        'Cancelamento'   => $dataCancelamento,
                        'externo_id'     => (string) $idContratoValido
                    ]);

                    // =========================
                    // INSERT CONTRATO
                    // =========================
                    DB::table('nando690_exclusivesis_sistema_proprio.contrato')->insert($dadosContrato);
                });

            } catch (Exception $e) {
                $this->inconsistencias[] = [
                    'Contrato' => $contrato->id ?? $contrato->Id ?? 'N/A',
                    'Erro'     => $e->getMessage()
                ];
            }
        }
    }

    /**
     * Busca forma de pagamento correspondente de maneira segura.
     */
    private function buscarFormaPagamento($contrato): int {
        $formaOrigem = $contrato->forma_pagamento ?? $contrato->FormaPagamento ?? '';

        if (empty($formaOrigem)) {
            return 1;
        }

        $dePara = DB::table('nando690_exclusivesis_sistema_proprio.contrato_formapagamento')
            ->where('descricao', 'LIKE', "%{$formaOrigem}%")
            ->first();

        return $dePara ? $dePara->id : 1;
    }

    /**
     * Retorna inconsistências encontradas.
     */
    public function getInconsistencias(): array {
        $return = [];
        foreach ($this->inconsistencias as $item) {
            $return[] = [
                'Contrato' => $item['Contrato'],
                'Erro'     => Str::limit($item['Erro'], 50, '...')
            ];
        }
        return $return;
    }
}