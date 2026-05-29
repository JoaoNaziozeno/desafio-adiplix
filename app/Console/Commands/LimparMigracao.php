<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LimparMigracao extends Command
{
    protected $signature = 'migrar:limpar';
    protected $description = 'Apaga apenas os dados inseridos pelo script de migração';

    public function handle() {
        if (
            !$this->confirm(
                'Tem certeza que deseja apagar os dados migrados? Isso não afetará dados nativos.'
            )
        ) {
            $this->info('Operação cancelada.');
            return Command::SUCCESS;
        }

        $this->warn('Iniciando limpeza dos dados migrados...');

        DB::beginTransaction();

        try {

            /**
             * 1. Busca IDs das pessoas migradas
             */
            $idClientesMigrados = DB::table('pessoa')
                ->whereNotNull('externo_id')
                ->where('externo_id', '!=', '')
                ->pluck('Id');

            /**
             * 2. Apaga contratos migrados
             */
            $contratosApagados = DB::table('contrato')
                ->whereNotNull('externo_id')
                ->where('externo_id', '!=', '')
                ->delete();

            /**
             * 3. Apaga endereços secundários
             * vinculados às pessoas migradas
             */
            $enderecosApagados = DB::table('pessoa_endereco')
                ->whereIn('IdPessoa', $idClientesMigrados)
                ->delete();

            /**
             * 4. Apaga e-mails das pessoas migradas
             */
            //$emailsApagados = DB::table('pessoa_email')
              //  ->whereIn('id_pessoa', $idClientesMigrados)
                //->delete();

            /**
             * 5. Apaga pessoas migradas
             */
            $pessoasApagadas = DB::table('pessoa')
                ->whereNotNull('externo_id')
                ->where('externo_id', '!=', '')
                ->delete();

            /**
             * 6. Apaga planos migrados
             */
            $planosApagados = DB::table('plano')
                ->whereNotNull('externo_id')
                ->where('externo_id', '!=', '')
                ->delete();

            DB::commit();

            $this->info("=== Limpeza concluída ===");

            $this->line("Contratos removidos: $contratosApagados");
            $this->line("Endereços removidos: $enderecosApagados");
            //$this->line("E-mails removidos: $emailsApagados");
            $this->line("Pessoas removidas: $pessoasApagadas");
            $this->line("Planos removidos: $planosApagados");

            return Command::SUCCESS;

        } catch (\Exception $e) {

            DB::rollBack();

            $this->error(
                "Erro ao limpar dados: " . $e->getMessage()
            );

            return Command::FAILURE;
        }
    }
}