<a id="help-vlan"><h1>Redes</h1></a>
<p>Este painel permite efectuar as seguintes operações sobre o CM:</p>
<ul>
    <li><a href="#help-vlan-manage">Adicionar/Remover Redes</a></li>
    <li><a href="#help-vlan-mac">Gerir da pool de endereços MAC</a></li>
    <li><a href="#help-vlan-interfaces">Gestão das interfaces de rede das máquinas virtuais</a></li>
    <li><a href="#help-vlan-list">Listar interfaces de rede</a></li>
</ul>
<br/>
<hr/>

<a id="help-vlan-manage"><h1>Adicionar/Remover Redes</h1></a>
<p>Para criar uma rede clica-se em <em>Adicionar rede</em>. A informação da rede consiste no seu nome
e ID (Caso a rede/vlan seja tagged o campo ID da rede refere-se à VLAN ID) (ver figura 3.5).
Para remover uma rede selecciona-se a rede pretendida e clica-se em Remover rede.</p>

<p><b>Nota: </b>As operações de adicionar/remover rede só estão disponíveis na versão ETVA
Enterprise.</p>

<p>A rede adicionada/removida é propagada a todos os nodes do CM.</p>
<a href="#help-vlan"><div>Início</div></a>
<hr/>

<a id="help-vlan-mac"><h1>Gerir da pool de endereços MAC</h1></a>
<p>Em Gestão da Pool de MAC é possivel criar a pool de endereços MAC. Para
além de adicionar MACs à pool, pode-se visualizar as redes associadas e os MACs ainda
disponíveis da pool.</p>
<a href="#help-vlan"><div>Início</div></a>
<hr/>

<a id="help-vlan-interfaces"><h1>Gestão das interfaces de rede das máquinas virtuais</h1></a>

<p>Seleccionando um registo da tabela de interfaces e acedendo ao sub-menu de contexto,
é possível remover a interface de rede associada a esse registo - Remover interface de
rede, ou alterar as interfaces de rede da máquina virtual associada ao registo seleccionado
- Gestão das interfaces de rede.</p>

<p>Na gestão de interfaces de uma máquina, dependendo do tipo de máquina virtual é possível
seleccionar os drivers das placas de rede (Esta opção está disponível para máquinas em HVM ou KVM.Os drivers disponíveis são: e1000, rtl8139
e virtio).</p>

<a href="#help-vlan"><div>Início</div></a>
<hr/>

<a id="help-vlan-list"><h1>Listar interfaces de rede</h1></a>
<p>É possível também listar as interfaces de uma rede, clicando sobre a rede pretendida.
    No painel abaixo aparecem as interfaces de rede associadas.</p>
<a href="#help-vlan"><div>Início</div></a>
<hr/>
