<?php

namespace App\Migracoes\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Migracoes\Payloads\PlanoPayload;

class PlanoMigrationService
{
    /**
     * Armazena inconsistências encontradas durante a migração.
     */
    private array $inconsistencias = [];

    /**
     * Executa a migração dos planos e retorna o mapa de IDs.
     */
    public function migrar(): array
    {
        $planosOrigem = DB::connection('sistema_proprio')
            ->table('contrato')
            ->select('plano', 'valor')
            ->groupBy('plano', 'valor')
            ->get();

        $mapaPlanos = [];

        foreach ($planosOrigem as $plano) {

            try {

                DB::transaction(function () use ($plano, &$mapaPlanos) {
                    $arrayPlanoDestino = PlanoPayload::criar([
                        'IdFilial'   => 1,
                        'Plano'      => trim($plano->plano),
                        'Valor'      => $plano->valor,
                        'Data'       => now()->format('Y-m-d'),
                        'Usuario'    => 1,
                        'externo_id' => trim($plano->plano) . '_' . $plano->valor
                    ]);

                    // Remove colunas inexistentes na tabela destino
                    unset($arrayPlanoDestino['plano_carteirinha_id']);
                    unset($arrayPlanoDestino['limite_parcelas_visiveis_no_app']);

                    // Insere no banco destino
                    $idPlanoCriado = DB::table('plano')
                        ->insertGetId($arrayPlanoDestino);

                    // Cria chave única para o mapa em memória
                    $chaveMapa = Str::slug($plano->plano) . '_' . (float)$plano->valor;

                    $mapaPlanos[$chaveMapa] = $idPlanoCriado;
                });

            } catch (Exception $e) {

                // Registra inconsistência e continua a migração
                $this->inconsistencias[] = [
                    'Plano' => trim($plano->plano),
                    'Erro'  => $e->getMessage()
                ];
            }
        }

        return $mapaPlanos;
    }

    /**
     * Retorna lista de inconsistências encontradas.
     */
    public function getInconsistencias(): array
    {
        return $this->inconsistencias;
    }
}