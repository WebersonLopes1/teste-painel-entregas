<?php

class EntregaRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    
public function listar(
    string $empresa = '',
    string $departamento = '',
    string $status = ''
): array {
    $sql = "SELECT e.id,
                   e.competencia,
                   e.vencimento,
                   e.data_entrega,
                   emp.id AS empresa_id,
                   emp.razao_social,
                   emp.cnpj,
                   o.nome AS obrigacao,
                   o.departamento
            FROM entregas e
            JOIN empresas emp ON emp.id = e.empresa_id
            JOIN obrigacoes o ON o.id = e.obrigacao_id
            WHERE 1=1";

    $parametros = [];

    if ($empresa !== '') {
        $sql .= " AND emp.razao_social LIKE :empresa";
        $parametros['empresa'] = '%' . $empresa . '%';
    }

    if ($departamento !== '') {
        $sql .= " AND o.departamento = :departamento";
        $parametros['departamento'] = $departamento;
    }

    if ($status === 'entregue') {
        $sql .= " AND e.data_entrega IS NOT NULL";
    } elseif ($status === 'atrasada') {
        $sql .= " AND e.data_entrega IS NULL
                  AND e.vencimento < CURDATE()";
    } elseif ($status === 'pendente') {
        $sql .= " AND e.data_entrega IS NULL
                  AND e.vencimento >= CURDATE()";
    }

    $sql .= " ORDER BY e.vencimento ASC, emp.razao_social ASC";

    $consulta = $this->pdo->prepare($sql);
    $consulta->execute($parametros);

    $entregas = $consulta->fetchAll();

    foreach ($entregas as &$entrega) {
        $entrega['id'] = (int) $entrega['id'];
        $entrega['empresa_id'] = (int) $entrega['empresa_id'];
        $entrega['status'] = calcularStatus($entrega);
    }

    return $entregas;
}


    public function resumo(): array
    {
        $sql = "SELECT COUNT(*) AS total,
                       COALESCE(SUM(data_entrega IS NOT NULL), 0) AS entregues,
                       COALESCE(SUM(data_entrega IS NULL AND vencimento >= CURDATE()), 0) AS pendentes,
                       COALESCE(SUM(data_entrega IS NULL AND vencimento < CURDATE()), 0) AS atrasadas
                  FROM entregas";

        return array_map('intval', $this->pdo->query($sql)->fetch());
    }
}
