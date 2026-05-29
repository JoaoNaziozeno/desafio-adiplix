<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Migracoes\Services\PlanoMigrationService;
use App\Migracoes\Services\PessoaMigrationService;
use App\Migracoes\Services\ContratoMigrationService;

class MigrarAdiplix extends Command
{
    protected $signature = 'migrar:desafio';
    protected $description = 'Orquestrador modular da migração Adiplix';

    public function handle()
    {
        $this->info("Iniciando o processo de migração de dados...");

        // =========================
        // MÓDULO DE PLANOS
        // =========================
        $this->comment("Processando Módulo de Planos...");

        $planoService = new PlanoMigrationService();

        $mapaPlanos = $planoService->migrar();

        $this->info("Planos processados.");

        // =========================
        // MÓDULO DE PESSOAS/CONTRATOS
        // =========================
        $this->comment("Processando Módulos de Contratos e Pessoas...");

        $pessoaService = new PessoaMigrationService();

        $contratoService = new ContratoMigrationService($pessoaService);
        $contratoService->migrar($mapaPlanos);
        $this->info("Contratos e pessoas processados.");

        // =========================
        // RELATÓRIO FINAL
        // =========================
        $this->exibirRelatorioFinal(
            $pessoaService->getMetricas(),
            $pessoaService->getInconsistencias(),
            $planoService->getInconsistencias(),
            $contratoService->getInconsistencias()
        );

        $this->info("=== Migração finalizada ===");

        return Command::SUCCESS;
    }

    private function exibirRelatorioFinal(
        array $metricas,
        array $inconsistenciasPessoas,
        array $inconsistenciasPlanos,
        array $inconsistenciasContratos
    ): void {

        $this->newLine();
        $this->line("--------------------------------------------------");
        $this->info("               RELATÓRIO DE MÉTRICAS              ");
        $this->line("--------------------------------------------------");
        $this->line("Total de Endereços Secundários criados: " . $metricas['total_multi_enderecos']);
        $this->line("Total de E-mails Inconsistentes ignorados: " . count($metricas['emails_inconsistentes']));
        $this->line("--------------------------------------------------");

        // EMAILS INVÁLIDOS
        if (count($metricas['emails_inconsistentes']) > 0) {
            $this->warn("Lista de E-mails Inconsistentes:");
            $this->table(
                ['ID Pessoa Origem', 'E-mail Inconsistente'],
                $metricas['emails_inconsistentes']
            );
        }

        //PESSOAS COM ERRO
        if (count($inconsistenciasPessoas) > 0) {
            $this->warn("Pessoas não migradas:");
            $this->table(
                ['ID Pessoa Origem', 'Erro'],
                $inconsistenciasPessoas
            );
        }

        // PLANOS COM ERRO
        if (count($inconsistenciasPlanos) > 0) {
            $this->warn("Planos não migrados:");
            $this->table(
                ['Plano', 'Erro'],
                $inconsistenciasPlanos
            );
        }

        // CONTRATOS COM ERRO
        if (count($inconsistenciasContratos) > 0) {
            $this->warn("Contratos não migrados:");
            $this->table(
                ['Contrato', 'Erro'],
                $inconsistenciasContratos
            );
        }
    }
}