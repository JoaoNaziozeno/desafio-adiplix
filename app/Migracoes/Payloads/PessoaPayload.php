<?php

namespace App\Migracoes\Payloads;

class PessoaPayload
{
    private const DEFAULTS = [
        'TipoPessoa'          => 1,
        'inscricao_estadual'  => 'isento',
        'inscricao_municipal' => null,
        'razao_social'        => null,
        'Longitude'           => '',
        'Latitude'            => '',
        'Rota'                => '9999',
        'IdStatusSorteio'     => 1,
        'DataLL'              => null,
        'ObsCobranca'         => '',
        'OrdemServico'        => null,
        'IdReligiao'          => null,
        'EstadoCivil'         => null,
        'Obito'               => null,
        'ponto_referencia'    => '',
    ];
    public static function criar(array $dados): array
    {
        $dados = array_replace(self::DEFAULTS, $dados);

        return [
            'IdStatusSorteio'     => $dados['IdStatusSorteio'],
            'NotaInterna'         => $dados['NotaInterna'] ?? null,
            'IdFilial'            => $dados['IdFilial'],
            'TipoPessoa'          => $dados['TipoPessoa'],
            'Nome'                => $dados['Nome'] ?? '',
            'Sexo'                => $dados['Sexo'] ?? null,
            'DataNascimento'      => $dados['DataNascimento'] ?? null,
            'Email'               => $dados['Email'] ?? '',
            'Telefone1'           => $dados['Telefone1'] ?? '',
            'Telefone2'           => $dados['Telefone2'] ?? '',
            'Documento'           => $dados['Documento'] ?? '',
            'inscricao_estadual'  => $dados['inscricao_estadual'],
            'inscricao_municipal' => $dados['inscricao_municipal'],
            'razao_social'        => $dados['razao_social'],
            'Nacionalidade'       => $dados['Nacionalidade'] ?? '',
            'IdEstadoCivil'       => $dados['IdEstadoCivil'] ?? null,
            'EstadoCivil'         => $dados['EstadoCivil'],
            'Cpf'                 => $dados['Cpf'] ?? '',
            'Cep'                 => $dados['Cep'] ?? '',
            'DataLL'              => $dados['DataLL'],
            'Longitude'           => $dados['Longitude'],
            'Latitude'            => $dados['Latitude'],
            'Rota'                => $dados['Rota'],
            'IdSetor'             => $dados['IdSetor'] ?? 0,
            'Estado'              => $dados['Estado'] ?? '',
            'Cidade'              => $dados['Cidade'] ?? '',
            'Setor'               => $dados['Setor'] ?? '',
            'Endereco'            => $dados['Endereco'] ?? '',
            'Numero'              => $dados['Numero'] ?? '',
            'Complemento'         => $dados['Complemento'] ?? '',
            'IdReligiao'          => $dados['IdReligiao'],
            'Religiao'            => $dados['Religiao'] ?? null,
            'DataCadastro'        => $dados['DataCadastro'] ?? null,
            'Obito'               => $dados['Obito'],
            'OrdemServico'        => $dados['OrdemServico'],
            'Profissao'           => $dados['Profissao'] ?? '',
            'Usuario'             => $dados['Usuario'] ?? 1,
            'Obs'                 => $dados['Obs'] ?? '',
            'ObsCobranca'         => $dados['ObsCobranca'],
            'ponto_referencia'    => $dados['ponto_referencia'],
            'NomePai'             => $dados['NomePai'],
            'NomeMae'             => $dados['NomeMae'],
            'IdAsaas'             => $dados['IdAsaas'] ?? '',
            'externo_id'          => $dados['externo_id'],
        ];
    }
}
