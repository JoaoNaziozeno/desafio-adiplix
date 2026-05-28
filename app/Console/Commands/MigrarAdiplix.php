<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Migracoes\Services\PlanoMigrationService;
use App\Migracoes\Services\PessoaMigrationService;
use App\Migracoes\Services\ContratoMigrationService;

class MigrarAdiplix extends Command
{
    // Assinatura do comando no terminal
    protected $signature = 'migrar:desafio';
    protected $description = 'Orquestrador modular da migração Adiplix';

    public function handle()
    {
        $this->info("Iniciando o processo de migração de dados...");

        // Inicia a transação para garantir que não salve dados parciais em caso de erro
        DB::beginTransaction();

        try {
            // 1. Processa o Módulo de Planos e guarda o mapa de IDs
            $this->comment("Processando Módulo de Planos...");
            $planoService = new PlanoMigrationService();
            $mapaPlanos = $planoService->migrar();
            $this->info("Planos migrados com sucesso.");

            // 2. Instancia o Módulo de Pessoas e de Contratos
            $pessoaService = new PessoaMigrationService();
            $contratoService = new ContratoMigrationService($pessoaService);

            // 3. Executa a migração dos Contratos (que cria as pessoas 1:1 automaticamente)
            $this->comment("Processando Módulos de Contratos e Pessoas...");
            $contratoService->migrar($mapaPlanos);
            $this->info("Contratos e pessoas migrados com sucesso.");

            // Se tudo correr bem, salva as alterações no banco
            DB::commit();
            $this->info("=== Migração concluída com sucesso no banco de dados ===");

            // 4. Exibe o Relatório do Módulo 4 no terminal
            $metricas = $pessoaService->getMetricas();
            $this->exibirRelatorioFinal($metricas);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            // Se houver qualquer falha, desfaz todas as inserções
            DB::rollBack();
            $this->error("Erro crítico na migração: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Monta o relatório visual exigido no Módulo 4.
     */
    private function exibirRelatorioFinal(array $metricas): void
    {
        $this->newLine();
        $this->line("--------------------------------------------------");
        $this->info("               RELATÓRIO DE MÉTRICAS              ");
        $this->line("--------------------------------------------------");
        $this->line("Total de Endereços Secundários criados: " . $metricas['total_multi_enderecos']);
        $this->line("Total de E-mails Inconsistentes ignorados: " . count($metricas['emails_inconsistentes']));
        $this->line("--------------------------------------------------");

        if (count($metricas['emails_inconsistentes']) > 0) {
            $this->warn("Lista de E-mails Inconsistentes:");
            
            // Exibe os e-mails inválidos em formato de tabela no terminal
            $this->table(
                ['ID Pessoa Origem', 'E-mail Inconsistente'],
                $metricas['emails_inconsistentes']
            );
        }
    }
}