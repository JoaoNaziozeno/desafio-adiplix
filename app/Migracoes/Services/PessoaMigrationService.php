<?php

namespace App\Migracoes\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Migracoes\Payloads\PessoaPayload;
use App\Migracoes\Payloads\PessoaEnderecoPayload;

class PessoaMigrationService
{
    private array $emailsInconsistentes = [];
    private int $totalMultiEnderecos = 0;

    /**
     * Processa o tratamento da pessoa e insere no destino.
     */
    public function migrarPessoa(object $pessoaOrigem): int
    {
        // 1. Tratamento dos dados (Módulo 2)
        $dadosTratados = [
            'IdFilial'       => 1,
            'Nome'           => trim($pessoaOrigem->nome),
            'Documento'      => $this->formatarCpfCnpj($pessoaOrigem->cpf_cnpj),
            'Cpf'            => $this->formatarCpfCnpj($pessoaOrigem->cpf_cnpj),
            'DataNascimento' => $pessoaOrigem->data_nascimento ? date('d/m/Y', strtotime($pessoaOrigem->data_nascimento)) : null,
            'Endereco'       => trim($pessoaOrigem->endereco),
            'Numero'         => trim($pessoaOrigem->numero),
            'Complemento'    => trim($pessoaOrigem->complemento),
            'Setor'          => Str::upper(trim($pessoaOrigem->setor)),
            'Cidade'         => !empty(trim($pessoaOrigem->cidade)) ? trim($pessoaOrigem->cidade) : 'Gurupi',
            'Estado'         => trim($pessoaOrigem->estado),
            'externo_id'     => $pessoaOrigem->id
        ];

        // 2. Passa pela Factory
        $payloadPessoa = PessoaPayload::criar($dadosTratados);
        $idPessoaDestino = DB::table('nando690_exclusivesis_sistema_proprio.pessoa')->insertGetId($payloadPessoa);

        // 3. Regra de Endereço Secundário (2.10 a 2.15)
        if (!empty(trim($pessoaOrigem->endereco2))) {
            $this->migrarEnderecoSecundario($idPessoaDestino, $pessoaOrigem);
        }

        // 4. Regra de E-mails (2.16)
        $this->migrarEmails($idPessoaDestino, $pessoaOrigem->id);

        return $idPessoaDestino;
    }

    private function migrarEnderecoSecundario(int $idPessoaDestino, object $origem): void
    {
        $dadosEndereco = PessoaEnderecoPayload::criar([
            'IdPessoa'    => $idPessoaDestino,
            'Endereco'    => trim($origem->endereco2),
            'Numero'      => trim($origem->numero2),
            'Complemento' => trim($origem->complemento2),
            'Setor'       => Str::upper(trim($origem->setor2)),
            'Cidade'      => !empty(trim($origem->cidade2)) ? trim($origem->cidade2) : 'Gurupi',
            'Estado'      => trim($origem->estado2),
            'externo_id'  => $origem->id
        ]);

        DB::table('nando690_exclusivesis_sistema_proprio.pessoa_endereco_secundario')->insert($dadosEndereco);
        $this->totalMultiEnderecos++;
    }

    private function migrarEmails(int $idPessoaDestino, int $idOrigemPessoa): void
    {
        $emails = DB::connection('sistema_proprio')->table('pessoa_email')->where('id_pessoa', $idOrigemPessoa)->get();
        
        foreach ($emails as $em) {
            $emailLimpo = trim($em->email);
            if (filter_var($emailLimpo, FILTER_VALIDATE_EMAIL)) {
                DB::table('nando690_exclusivesis_sistema_proprio.pessoa_email')->insert([
                    'id_pessoa' => $idPessoaDestino,
                    'email'     => $emailLimpo
                ]);
            } else {
                $this->emailsInconsistentes[] = ['id_pessoa_origem' => $idOrigemPessoa, 'email' => $em->email];
            }
        }
    }

    private function formatarCpfCnpj($raw) {
        $d = Str::replaceMatches('/\D+/', '', (string)$raw);
        if (strlen($d) === 11) return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "$1.$2.$3-$4", $d);
        if (strlen($d) === 14) return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "$1.$2.$3/$4-$5", $d);
        return $raw;
    }

    public function getMetricas(): array {
        return [
            'emails_inconsistentes' => $this->emailsInconsistentes,
            'total_multi_enderecos' => $this->totalMultiEnderecos
        ];
    }
}