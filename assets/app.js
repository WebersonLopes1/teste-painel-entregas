const API = 'api';

let entregas = [];

const rotulosStatus = {
  entregue: 'Entregue',
  pendente: 'Pendente',
  atrasada: 'Atrasada',
};

function escaparHtml(texto) {
  const div = document.createElement('div');
  div.textContent = texto ?? '';
  return div.innerHTML;
}

// Recebe AAAA-MM-DD e devolve DD/MM/AAAA, sem converter fuso horário.
function formatarData(data) {
  if (!data) {
    return '—';
  }
  const [ano, mes, dia] = data.split('-');
  return `${dia}/${mes}/${ano}`;
}

// Recebe AAAA-MM e devolve MM/AAAA.
function formatarCompetencia(competencia) {
  const [ano, mes] = competencia.split('-');
  return `${mes}/${ano}`;
}

function mostrarAviso(mensagem, tipo = 'sucesso') {
  const aviso = document.getElementById('aviso');
  aviso.textContent = mensagem;
  aviso.className = tipo === 'erro' ? 'aviso aviso--erro' : 'aviso';
  aviso.hidden = false;
  clearTimeout(mostrarAviso.timer);
  mostrarAviso.timer = setTimeout(() => { aviso.hidden = true; }, 4000);
}

async function marcarEntrega(id) {
  try {
    const resposta = await fetch(`${API}/marcar_entrega.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });

    const dados = await resposta.json();

    if (!resposta.ok) {
      mostrarAviso(dados.erro || 'Erro ao marcar entrega.', 'erro');
      return;
    }

    mostrarAviso(dados.mensagem);
    await carregarEntregas();
    await carregarResumo();
  } catch (erro) {
    mostrarAviso('Erro de comunicação com a API.', 'erro');
  }
}

async function carregarResumo() {
  try {
    const resposta = await fetch(`${API}/resumo.php`);
    const resumo = await resposta.json();

    if (!resposta.ok) {
      mostrarAviso(resumo.erro || 'Erro ao carregar o resumo.', 'erro');
      return;
    }

    document.getElementById('resumo-total').textContent = resumo.total;
    document.getElementById('resumo-entregues').textContent = resumo.entregues;
    document.getElementById('resumo-pendentes').textContent = resumo.pendentes;
    document.getElementById('resumo-atrasadas').textContent = resumo.atrasadas;
  } catch (erro) {
    mostrarAviso('Não foi possível conectar à API.', 'erro');
  }
}

async function carregarEntregas() {
  try {

const parametros = new URLSearchParams({
  empresa: document.getElementById('filtro-empresa').value.trim(),
  departamento: document.getElementById('filtro-departamento').value,
  status: document.getElementById('filtro-status').value
});

const resposta = await fetch(`${API}/entregas.php?${parametros}`);
    const dados = await resposta.json();

    if (!resposta.ok) {
      entregas = [];
      mostrarAviso(dados.erro || 'Erro ao carregar as entregas.', 'erro');
    } else {
      entregas = dados;
    }
  } catch (erro) {
    entregas = [];
    mostrarAviso('Não foi possível conectar à API.', 'erro');
  }

    renderizarTabela();
  atualizarSelecionadas();

}

function renderizarTabela() {
  const corpo = document.getElementById('lista-entregas');

  if (entregas.length === 0) {
    corpo.innerHTML = '<tr><td colspan="9" class="tabela__vazio">Nenhuma entrega encontrada.</td></tr>';
    return;
  }

  
corpo.innerHTML = entregas.map((entrega) => `
    <tr>
      <td>
        ${entrega.status !== 'entregue'
          ? `<input type="checkbox"
                    class="selecionar-entrega"
                    value="${entrega.id}">`
          : '—'}
      </td>
      <td>
        <span class="tabela__empresa">${escaparHtml(entrega.razao_social)}</span>

        <span class="tabela__cnpj">${escaparHtml(entrega.cnpj)}</span>
      </td>
      <td>${escaparHtml(entrega.obrigacao)}</td>
      <td>${escaparHtml(entrega.departamento)}</td>
      <td>${formatarCompetencia(entrega.competencia)}</td>
      <td>${formatarData(entrega.vencimento)}</td>
      <td>${formatarData(entrega.data_entrega)}</td>
      <td><span class="status status--${entrega.status}">${rotulosStatus[entrega.status]}</span></td>

<td>
  ${entrega.status !== 'entregue'
    ? `<button class="botao botao--secundario"
        onclick="marcarEntrega(${entrega.id})">
        Marcar como entregue
       </button>`
    : '—'}
</td>

    </tr>
  `).join('');
}
async function carregarOpcoes() {
  try {
    const resposta = await fetch(`${API}/opcoes.php`);
    const dados = await resposta.json();

    if (!resposta.ok) {
      mostrarAviso(dados.erro || 'Erro ao carregar opções.', 'erro');
      return;
    }

    const campoEmpresa = document.getElementById('cadastro-empresa');
    const campoObrigacao = document.getElementById('cadastro-obrigacao');

    dados.empresas.forEach(empresa => {
      const opcao = document.createElement('option');
      opcao.value = empresa.id;
      opcao.textContent = empresa.razao_social;
      campoEmpresa.appendChild(opcao);
    });

    dados.obrigacoes.forEach(obrigacao => {
      const opcao = document.createElement('option');
      opcao.value = obrigacao.id;
      opcao.textContent = obrigacao.nome;
      campoObrigacao.appendChild(opcao);
    });

  } catch (erro) {
    mostrarAviso('Erro ao carregar empresas e obrigações.', 'erro');
  }
}

carregarResumo();
carregarEntregas();
carregarOpcoes();

document.getElementById('form-filtros').addEventListener('submit', function(evento) {
  evento.preventDefault();
  carregarEntregas();
});

document.getElementById('limpar-filtros').addEventListener('click', function() {
  document.getElementById('form-filtros').reset();
  carregarEntregas();
});

document.getElementById('form-cadastro').addEventListener('submit', async function(evento) {
  evento.preventDefault();

  const dados = {
    empresa_id: document.getElementById('cadastro-empresa').value,
    obrigacao_id: document.getElementById('cadastro-obrigacao').value,
    competencia: document.getElementById('cadastro-competencia').value,
    vencimento: document.getElementById('cadastro-vencimento').value
  };

  try {
    const resposta = await fetch(`${API}/cadastrar_entrega.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(dados)
    });

    const resultado = await resposta.json();

    if (!resposta.ok) {
      mostrarAviso(resultado.erro || 'Erro ao cadastrar entrega.', 'erro');
      return;
    }

    mostrarAviso(resultado.mensagem);
    evento.target.reset();

    await carregarEntregas();
    await carregarResumo();

  } catch (erro) {
    mostrarAviso('Erro de comunicação com a API.', 'erro');
  }
});

function atualizarSelecionadas() {
  const quantidade = document.querySelectorAll(
    '.selecionar-entrega:checked'
  ).length;

  const botao = document.getElementById('marcar-selecionadas');

  botao.disabled = quantidade === 0;
  botao.textContent = `Marcar selecionadas como entregues (${quantidade})`;
}

document.getElementById('lista-entregas').addEventListener('change', function(evento) {
  if (evento.target.classList.contains('selecionar-entrega')) {
    atualizarSelecionadas();
  }
});

document.getElementById('marcar-selecionadas').addEventListener('click', async function() {
  const ids = Array.from(
    document.querySelectorAll('.selecionar-entrega:checked')
  ).map(campo => Number(campo.value));

  if (ids.length === 0) {
    return;
  }

  try {
    const resposta = await fetch(`${API}/marcar_varias.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ids })
    });

    const dados = await resposta.json();

    if (!resposta.ok) {
      mostrarAviso(dados.erro || 'Erro ao marcar entregas.', 'erro');
      return;
    }

    mostrarAviso(dados.mensagem);

    await carregarEntregas();
    await carregarResumo();
    atualizarSelecionadas();

  } catch (erro) {
    mostrarAviso('Erro de comunicação com a API.', 'erro');
  }
});
