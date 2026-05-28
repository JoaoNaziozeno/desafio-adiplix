<?php

namespace App\Migracoes\Payloads;

class PessoaEnderecoPayload
{
    private const DEFAULTS = [
        'Telefone1'   => '',
        'Telefone2'   => '',
        'Email'       => '',
        'Complemento' => '',
    ];

    public static function criar(array $dados): array
    {
        $dados = array_replace(self::DEFAULTS, $dados);

        return [
            'IdPessoa'    => $dados['IdPessoa'],
            'Telefone1'   => $dados['Telefone1'],
            'Telefone2'   => $dados['Telefone2'],
            'Email'       => $dados['Email'],
            'Endereco'    => $dados['Endereco'] ?? '',
            'Numero'      => $dados['Numero'] ?? '',
            'Complemento' => $dados['Complemento'],
            'Setor'       => $dados['Setor'] ?? '',
            'Cep'         => $dados['Cep'] ?? '',
            'Cidade'      => $dados['Cidade'] ?? '',
            'Estado'      => $dados['Estado'] ?? '',
            'externo_id'  => $dados['externo_id'] ?? null,
        ];
    }
}
