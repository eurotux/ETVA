<div class="help">
<div class="help-section">
<a id="help-network-list-main"><h1>Lista de interfaces de rede do servidor</h1></a>

<p>A janela de gestão de interfaces é constituída por uma grelha que lista as interfaces existentes, e
    indica o <em>MAC Address</em> e a rede à qual está ligada.
</p>

<p>Estão disponíveis as seguintes opções relativas às interfaces de rede, que podem ser acedidas através do menu de contexto:</p>
<ul>
    <li><a href="#help-network-list-manage">Gestão</a></li>
    <li><a href="#help-network-list-remove">Remover</a></li>
</ul>
<p><b>Nota: </b>Para que as alterações tenham efeito, o agente de virtualização deve estar em execução.</p>
<br/>
<hr/>

<a id="help-network-list-manage"><h1>Gestão das interfaces de rede</h1></a>
<p>Esta operação é uma atalho que abre a janela de gestão de interfaces de rede. Pode ver mais informação <a href="#help-network-main">aqui</a>.</p>
<hr/>

<a id="help-network-list-remove"><h1>Remover interface de rede</h1></a>
<p>Esta operação é uma atalho que remove a interface de rede seleccionada. Pode ver mais informação <a href="#help-network-delete">aqui</a>.</p>
<hr/>
</div>

<div class="help-section">
<a id="help-network-main"><h1>Alterar interfaces de rede do servidor</h1></a>

<p>A janela de gestão de interfaces é constituída por uma grelha que lista as interfaces existentes, e
    indica o <em>MAC Address</em> e a rede à qual está ligada.
</p>

<p>Estão disponíveis as seguintes opções:</p>
<ul>
    <li><a href="#help-network-add">Adicionar interface</a></li>
    <li><a href="#help-network-edit">Editar interface</a></li>
    <li><a href="#help-network-delete">Remover interface</a></li>
    <li><a href="#help-network-moveupdown">Mover para cima/baixo</a></li>
</ul>
<p><b>Nota: </b>Para que as alterações tenham efeito, o agente de virtualização deve estar em execução.</p>
<br/>
<hr/>


<a id="help-network-add"><h1>Adicionar interface</h1></a>
<p>Para criar uma interface de rede, seleccionar <em>Adicionar interface</em> na barra de opções superior.
    É acrescentada uma linha à lista de interfaces. De seguida é necessário seleccionar uma rede e o driver a utilizar.
</p>

<p>Para seleccionar a rede efectuar duplo clique sobre o texto <em>Seleccione uma rede</em>, que aparece na coluna <em>Network</em>.
</p>
<p>O procedimento necessário para escolher o driver é análogo. Basta efectuar duplo clique sobre o texto <em>Seleccione o modelo</em>.
</p>
<a href="#help-network-main"><div>Início</div></a>
<hr/>

<a id="help-network-edit"><h1>Editar interface</h1></a>
<p>Para editar uma interface de rede, seleccionar <em>Editar interface</em> na barra de opções superior.
    De seguida é pode seleccionar uma rede, à qual a interface ficará associada.
    Para além desta opção é também possível escolher o tipo de driver a utilizar na interface.
</p>

<p><b>Nota 1: </b>Em alternativa pode optar por seleccionar a interface que pretende alterar e efectuar duplo clique, na coluna <em>Network</em>.</p>
<p><b>Nota 2: </b>Em máquinas virtuais KVM, é recomendado o uso do driver <em>virtio</em>.
    Porém esta escolha implica a instalação do driver respectivo na máquina virtual</p>
<a href="#help-network-main"><div>Início</div></a>
<hr/>

<a id="help-network-delete"><h1>Remover interface</h1></a>
<p>Para remover uma interface de rede, seleccionar a interface em causa e escolher a opção <em>Apagar interface</em> na barra de opções superior.
</p>
<a href="#help-network-main"><div>Início</div></a>
<hr/>

<a id="help-network-moveupdown"><h1>Mover para cima/baixo</h1></a>
<p>Esta opção permite alterar a ordem das interfaces de rede.
</p>
<a href="#help-network-main"><div>Início</div></a>
<hr/>

</div>
</div>
