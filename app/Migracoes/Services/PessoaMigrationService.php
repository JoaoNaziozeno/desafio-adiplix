<?php

namespace App\Migracoes\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Migracoes\Payloads\PessoaPayload;
use App\Migracoes\Payloads\PessoaEnderecoPayload;

class PessoaMigrationService
{
    private array $emailsInconsistentes = [];
    private array $inconsistencias = [];

    private int $totalMultiEnderecos = 0;

    public function migrarPessoa(object $pessoaOrigem): ?int {
        try {

            return DB::transaction(function () use ($pessoaOrigem) {

                // =========================
                // TRATAMENTO DOS DADOS
                // =========================

                $emailOrigem = DB::connection('sistema_proprio')
                    ->table('pessoa_email')
                    ->where('id_pessoa', $pessoaOrigem->id)
                    ->where('email', 'LIKE', '%@%')
                    ->value('email') ?? '';

                $dadosTratados = [

                    'IdFilial'       => 1,
                    'Nome'           => trim($pessoaOrigem->nome ?? ''),
                    'Documento'      => $this->formatarCpfCnpj($pessoaOrigem->cpf_cnpj ?? ''),
                    'Cpf'            => $this->formatarCpfCnpj($pessoaOrigem->cpf_cnpj ?? ''),
                    'DataNascimento' => (!empty($pessoaOrigem->data_nascimento) && $pessoaOrigem->data_nascimento !== '0000-00-00') ? date('Y-m-d', strtotime($pessoaOrigem->data_nascimento)) : '1900-01-01',
                    'Email'          => trim($emailOrigem),
                    'Endereco'       => trim($pessoaOrigem->endereco ?? ''),
                    'Numero'         => trim($pessoaOrigem->numero ?? ''),
                    'Complemento'    => trim($pessoaOrigem->complemento ?? ''),
                    'Setor'          => Str::upper(trim($pessoaOrigem->setor ?? '')),
                    'Cidade'         => !empty(trim($pessoaOrigem->cidade ?? '')) ? trim($pessoaOrigem->cidade) : 'Gurupi',
                    'Estado'         => trim($pessoaOrigem->estado ?? ''),
                    'DataCadastro'   => now()->format('Y-m-d H:i:s'),
                    'externo_id'     => $pessoaOrigem->id
                ];

                // =========================
                // PAYLOAD PESSOA
                // =========================
                $payloadPessoa = PessoaPayload::criar($dadosTratados);
                unset($payloadPessoa['ponto_referencia']);

                $payloadPessoa['DataCadastro'] = $dadosTratados['DataCadastro'];
                
                // =========================
                // INSERT PESSOA
                // =========================
                $idPessoaDestino = DB::table('nando690_exclusivesis_sistema_proprio.pessoa')->insertGetId($payloadPessoa);

                // =========================
                // ENDEREÇO SECUNDÁRIO
                // =========================
                if ($this->possuiEnderecoSecundario($pessoaOrigem)) {
                    $this->migrarEnderecoSecundario(
                        $idPessoaDestino,
                        $pessoaOrigem
                    );
                }

                // =========================
                // E-MAILS
                // =========================
                $this->migrarEmails(
                    $idPessoaDestino,
                    $pessoaOrigem->id
                );

                return $idPessoaDestino;
            });

        } catch (Exception $e) {

            // Registra inconsistência e continua processamento
            $this->inconsistencias[] = [
                'Pessoa' => $pessoaOrigem->id ?? 'N/A',
                'Erro'   => $e->getMessage()
            ];

            return null;
        }
    }

    /**
     * Migra endereço secundário.
     */
    private function migrarEnderecoSecundario(
        int $idPessoaDestino,
        object $origem
    ): void {

        $dadosEndereco = PessoaEnderecoPayload::criar([
            'IdPessoa'    => $idPessoaDestino,
            'Endereco'    => trim($origem->endereco2 ?? ''),
            'Numero'      => trim($origem->numero2 ?? ''),
            'Complemento' => trim($origem->complemento2 ?? ''),
            'Setor'       => Str::upper(trim($origem->setor2 ?? '')),
            'Cidade'      => !empty(trim($origem->cidade2 ?? '')) ? trim($origem->cidade2) : 'Gurupi',
            'Estado'      => trim($origem->estado2 ?? ''),
            'externo_id'  => $origem->id
        ]);

        unset($dadosEndereco['externo_id']);
        
        DB::table('nando690_exclusivesis_sistema_proprio.pessoa_endereco')->insert($dadosEndereco);

        $this->totalMultiEnderecos++;
    }

    /**
     * Migra e valida e-mails.
     */
private function migrarEmails(int $idPessoaDestino, int $idOrigemPessoa): void {
        $emails = DB::connection('sistema_proprio')
            ->table('pessoa_email')
            ->where('id_pessoa', $idOrigemPessoa)
            ->get();

        foreach ($emails as $em) {
            $emailLimpo = trim($em->email ?? '');

            // Se o e-mail for inválido e não estiver vazio, adiciona às inconsistências
            if (!filter_var($emailLimpo, FILTER_VALIDATE_EMAIL) && !empty($emailLimpo)) {
                $this->emailsInconsistentes[] = [
                    'id_pessoa_origem' => $idOrigemPessoa,
                    'email'            => $em->email
                ];
            }
        }
    }

    /**
     * Verifica se possui endereço secundário.
     */
    private function possuiEnderecoSecundario(object $pessoa): bool {
        return
            trim($pessoa->endereco2 ?? '') !== '' ||
            trim($pessoa->numero2 ?? '') !== '' ||
            trim($pessoa->complemento2 ?? '') !== '' ||
            trim($pessoa->setor2 ?? '') !== '' ||
            trim($pessoa->cidade2 ?? '') !== '' ||
            trim($pessoa->estado2 ?? '') !== '';
    }

    /**
     * Formata CPF/CNPJ.
     */
    private function formatarCpfCnpj($raw): string {
        $d = Str::replaceMatches('/\D+/', '', (string) $raw);
        if (strlen($d) === 11) {
            return preg_replace(
                "/(\d{3})(\d{3})(\d{3})(\d{2})/",
                "$1.$2.$3-$4",
                $d
            );
        }

        if (strlen($d) === 14) {
            return preg_replace(
                "/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/",
                "$1.$2.$3/$4-$5",
                $d
            );
        }

        return (string) $raw;
    }

    /**
     * Retorna métricas do relatório.
     */
    public function getMetricas(): array {
        return [
            'emails_inconsistentes' => $this->emailsInconsistentes,
            'total_multi_enderecos' => $this->totalMultiEnderecos
        ];
    }

    /**
     * Retorna inconsistências encontradas.
     */
    public function getInconsistencias(): array {
        return $this->inconsistencias;
    }
}