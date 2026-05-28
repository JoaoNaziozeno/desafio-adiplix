<?php

namespace App\Migracoes\Payloads;

class ContratoPayload
{
    public static function criar(array $dados): array
    {
        return [
            'Id'                => (int) $dados['Id'],
            'IdFilial'          => (int) ($dados['IdFilial'] ?? 1),
            'Matricula'         => (string) ($dados['Matricula'] ?? ''),
            'IdPessoa'          => (int) $dados['IdPessoa'],
            'IdPlano'           => (int) ($dados['IdPlano'] ?? 1),
            'DataContrato'      => $dados['DataContrato'] ?? null,
            'DataTransferencia' => $dados['DataTransferencia'] ?? null,
            'Referencia'        => $dados['Referencia'] ?? null,
            'RefParcelasTrans'  => $dados['RefParcelasTrans'] ?? null,
            'RefValorParcelas'  => $dados['RefValorParcelas'] ?? '0.00',
            'Vencimento'        => (string) ($dados['Vencimento'] ?? '01'),
            'MesVigencia'       => $dados['MesVigencia'] ?? null,
            'FormaPagamento'    => (int) ($dados['FormaPagamento'] ?? 1),
            'Comissao'          => (float) ($dados['Comissao'] ?? 0),
            'Cobrador'          => (int) ($dados['Cobrador'] ?? 0),
            'CobradorAnterior'  => (int) ($dados['CobradorAnterior'] ?? 0),
            'DataAlteracao'     => $dados['DataAlteracao'] ?? null,
            'Vendedor'          => (int) ($dados['Vendedor'] ?? 0),
            'Situacao'          => (string) ($dados['Situacao'] ?? 'Carência'),
            'Total'             => (float) ($dados['Total'] ?? 0),
            'Data'              => $dados['Data'] ?? null,
            'Cancelamento'      => $dados['Cancelamento'] ?? null,
            'Quitado'           => $dados['Quitado'] ?? null,
            'Obs'               => (string) ($dados['Obs'] ?? ''),
            'IdUsuario'         => (int) ($dados['IdUsuario'] ?? 1),
            'EmissaoCarneSicoob'=> (int) ($dados['EmissaoCarneSicoob'] ?? 1),
            'externo_id'        => (string) ($dados['externo_id'] ?? ''),
        ];
    }
}
