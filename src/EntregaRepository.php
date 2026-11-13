<?php

class EntregaRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    

public function listar(
    string $empresa = '',
    string $departamento = '',
    string $status = '',
    string $competencia = ''
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

    if ($competencia !== '') {
    $sql .= " AND e.competencia = :competencia";
    $parametros['competencia'] = $competencia;
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

public function marcarComoEntregue(int $id): bool
{
    $sql = "UPDATE entregas
            SET data_entrega = CURDATE()
            WHERE id = :id
              AND data_entrega IS NULL";

    $consulta = $this->pdo->prepare($sql);
    $consulta->execute(['id' => $id]);

    return $consulta->rowCount() > 0;
}

public function listarEmpresas(): array
{
    $sql = "SELECT id, razao_social
            FROM empresas
            ORDER BY razao_social";

    return $this->pdo->query($sql)->fetchAll();
}

public function listarObrigacoes(): array
{
    $sql = "SELECT id, nome, departamento
            FROM obrigacoes
            ORDER BY nome";

    return $this->pdo->query($sql)->fetchAll();
}

public function cadastrar(int $empresaId, int $obrigacaoId, string $competencia, string $vencimento): void
{
    // Verifica se a empresa e a obrigação existem.
    $consulta = $this->pdo->prepare(
        "SELECT
            (SELECT COUNT(*) FROM empresas WHERE id = ?) AS empresas,
            (SELECT COUNT(*) FROM obrigacoes WHERE id = ?) AS obrigacoes"
    );
    $consulta->execute([$empresaId, $obrigacaoId]);
    $dados = $consulta->fetch();

    if (!$dados['empresas'] || !$dados['obrigacoes']) {
        throw new InvalidArgumentException('Empresa ou obrigação inválida.');
    }

    // Impede cadastrar a mesma obrigação duas vezes na mesma competência.
    $consulta = $this->pdo->prepare(
        "SELECT COUNT(*) FROM entregas
         WHERE empresa_id = ?
           AND obrigacao_id = ?
           AND competencia = ?"
    );
    $consulta->execute([$empresaId, $obrigacaoId, $competencia]);

    if ($consulta->fetchColumn() > 0) {
        throw new InvalidArgumentException('Esta entrega já está cadastrada para esta competência.');
    }

    // Insere a nova entrega ainda não concluída.
    $consulta = $this->pdo->prepare(
        "INSERT INTO entregas
         (empresa_id, obrigacao_id, competencia, vencimento, data_entrega)
         VALUES (?, ?, ?, ?, NULL)"
    );

    $consulta->execute([
        $empresaId,
        $obrigacaoId,
        $competencia,
        $vencimento
    ]);
}


public function marcarVariasComoEntregues(array $ids): int
{
    if (empty($ids)) {
        return 0;
    }

    $marcadores = implode(',', array_fill(0, count($ids), '?'));

    $sql = "UPDATE entregas
            SET data_entrega = CURDATE()
            WHERE id IN ($marcadores)
              AND data_entrega IS NULL";

    $consulta = $this->pdo->prepare($sql);
    $consulta->execute($ids);

    return $consulta->rowCount();
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
