<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LimparMigracao extends Command
{
    // O comando que você vai digitar no terminal
    protected $signature = 'migrar:limpar';
    protected $description = 'Apaga apenas os dados inseridos pelo script de migração';

    public function handle()
    {
        if (!$this->confirm('Tem certeza que deseja apagar os dados migrados? Isso não afetará dados nativos.')) {
            $this->info('Operação cancelada.');
            return Command::SUCCESS;
        }

        $this->warn('Iniciando limpeza dos dados migrados...');

        // Usamos uma transação para garantir que ou apaga tudo ou nada
        DB::beginTransaction();

        try {
            // 1. Apagar Contratos migrados
            // Identificados porque possuem valor no campo 'externo_id'
            $contratosApagados = DB::table('contrato')
                ->whereNotNull('externo_id')
                ->where('externo_id', '!=', '')
                ->delete();

            // 2. Apagar Endereços Secundários migrados
            $enderecosApagados = DB::table('pessoa_endereco_secundario')
                ->whereNotNull('externo_id')
                ->where('externo_id', '!=', '')
                ->delete();

            // 3. Apagar E-mails cadastrados na migração
            // Como a tabela pessoa_email não tem externo_id, apagamos os e-mails das pessoas que vieram da migração
            $idClientesMigrados = DB::table('pessoa')
                ->whereNotNull('externo_id')
                ->where('externo_id', '!=', '')
                ->pluck('id');

            $emailsApagados = DB::table('pessoa_email')
                ->whereIn('id_pessoa', $idClientesMigrados)
                ->delete();

            // 4. Apagar Pessoas migradas
            $pessoasApagadas = DB::table('pessoa')
                ->whereNotNull('externo_id')
                ->where('externo_id', '!=', '')
                ->delete();

            // 5. Apagar Planos migrados
            // No PlanoMigrationService colocamos o usuário como 'Migracao_Adiplix'
            $planosApagados = DB::table('plano')
                ->where('Usuario', 'Migracao_Adiplix')
                ->delete();

            DB::commit();

            // Exibe o feedback do que foi limpo
            $this->info("=== Limpeza concluída ===");
            $this->line("Contratos removidos: $contratosApagados");
            $this->line("Endereços secundários removidos: $enderecosApagados");
            $this->line("E-mails removidos: $emailsApagados");
            $this->line("Pessoas removidas: $pessoasApagadas");
            $this->line("Planos removidos: $planosApagados");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Erro ao limpar dados: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}