<div class="help">
<div class="help-section">
    <a id="help-clusterWz-main"><h1>Assitente de configuração de datacenters virtuais</h1></a>
    <p>O assistente de criação de datacenter, guia-o através dos seguintes passos:</p>

    <ul>
        <li><a href="#help-clusterWz-name">Definição do nome do <em>Datacenter</em></a></li>
        <li><a href="#help-clusterWz-net">Configuração de rede</a></li>
    </ul>
    <br/>
    <hr/>

    <a id="help-clusterWz-name"><h2>Nome do datacenter</h2></a>
    <p>Neste passo é criado o novo datacenter. Deve indicar o nome pretendido e selecionar a opção <em>criar</em></p>
    <p>É apresentada uma mensagem com o sucesso da operação e, caso o datacenter tenha sido criado, a opção <em>seguinte</em> tornar-se-á disponível.</p>
    <a href="#help-clusterWz-main"><div>Início</div></a>
    <hr/>

    <a id="help-clusterWz-net"><h2>Configuração da rede</h2></a>
    <p>Neste passo é possível acrescentar redes. Podem existir algumas redes pré-definidas, por exemplo a rede <em>Management</em>.</p>
    <p>Após as alterações pretendidas seleccione a opção <em>seguinte</em>.</p>
    <a href="#help-clusterWz-main"><div>Início</div></a>
    <hr/>

    <a id="help-edit"><h1>Editar datacenter virtual</h1></a>
    <p>A edição de um datacenter virtual permite a configuração de:</p>

    <ul>
        <li><a href="#help-edit-name">Nome do <em>Datacenter</em></a></li>
        <li><a href="#help-edit-nodeha">Nó com alta disponibilidade</a></li>
    </ul>
    <br/>
    <hr/>

    <a id="help-edit-name"><h2>Nome do datacenter</h2></a>
    <p>Neste formulário é possível alterar o nome do <em>datacenter virtual</em>.</em></p>
    <p><b>Nota: </b>O nome deverá começar por letras e só pode ser formado por letras, número, hífen e </em>underscore</em>.</p>
    <a href="#help-edit"><div>Início</div></a>
    <hr/>

    <a id="help-edit-nodeha"><h2>Nó com alta disponibilidade</h2></a>
    <p>Nesta opção é possível activar alta disponibilidade nos nós do <em>datacenter</em> segundo uma das seguintes opções:</p>
    <p><b>Tolerância de hosts a falha: </b> número de hosts em falha em que será garantido alta disponibilidade, ficando a alocação de recursos limitada para garantir alta disponibilidade do número de hosts definido;</p>
    <p><b>Percentagem de recursos reservada a failover: </b> percentagem de recursos reservada para garantir a alta disponibilidade dos serviços mais críticos;</p>
    <p><b>Nó suplente:</b> é definido um nó que garante a alta disponibilidade no caso de falha de um dos nós. Este nó suplente deverá ter recursos necessário para garantir a disponibilidade dos servidores críticos do nó em falha.</p>
    <p><b>Nota: </b>A opção <em>Nó com alta disponibilidade</em> só estará disponível se a configuração de <em>fencing</em> estiver definida em todos os nós</p>

    <a href="#help-edit"><div>Início</div></a>
    <hr/>
</div>

