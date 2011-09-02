<a id="help-left-panel-main"><h1><em><b>Nodes</b></em> (painel esquerdo)</h1></a>

<p>O painel <em>Nodes</em> pertence à tela principal do <em>Central Management</em> e lista as máquinas reais/servidores de virtualização
(as máquinas associadas a cada <em>node</em>).</p>

<p>O primeiro <em>node</em> visível é o <b><em>Main</em></b>, sob o qual se encontram os vários servidores de virtualização registados no <em>CM</em>.</p>

<p>Ao seleccinar um <em>node</em> (servidor de virtualização) é carregada a informação correspondente no painel principal (painel da direita).</p>

<p>Ao clicar com o botão direito do rato sobre o <em>node</em> acede-se ao menu de opções, onde é possível seleccionar efectuar as seguintes operações:</p>

<ul>
    <li>Inicializar</li>
    <ul>
        <li><a href="#help-left-panel-authorize">Autorizar <em>node</em></a></li>
        <li><a href="#help-left-panel-restart">Re-inicializar <em>node</em></a></li>
    </ul>
    <li>Node</li>
    <ul>
        <li><a href="#help-left-panel-loadnode">Carregar <em>node</em</a></li>
        <li><a href="#help-left-panel-hostname">Alterar <em>Hostname</em></a></li>
        <li><a href="#help-left-panel-connectivity">Opções de conectividade</a></li>
        <li><a href="#help-left-panel-keymap">Alterar <em>keymap</em></a></li>
        <li><a href="#help-left-panel-status">Verificar o estado do <em>node</em></a></li>
        <li><a href="#help-left-panel-remove">Remover <em>node</em></a></li>
    </ul>
</ul>
<p><b>Nota: </b><!--A lista de operações correspondentes aos pontos <em>Inicializar</em> e <em>Node</em>, são referentes ao <em>Node</em> onde foi solicitado o menu.-->
Caso tenha sido seleccionado um servidor ao invés de um <em>node</em>, todas as operações são referentes ao <b><em>node</em> pai</b>, com excepção da opção <em>Alterar keymap</em>.
</p>
<br/>
<hr/>

<a id="help-left-panel-authorize"><h1>Autorizar <em><b>node</b></em></h1></a>
<p>Quando um <em>node</em>, com o agente de virtualização instalado, é adicionado à rede, o cental management é notificado da sua existência.
Caso a afirmação anterior se verifique, o <em>node</em> aparece listado na árvore de nodes.</p>
<p>Para tornar possível a gestão do <em>node</em> é necessário seleccionar a opção <em>Autorizar node</em>.</p>
<a href="#help-left-panel-main"><div>Início</div></a>
<hr/>

<a id="help-left-panel-restart"><h1>Re-inicializar <em><b>node</b></em></h1></a>
<p>A opção <em>Re-inicializar node</em> faz restart ao agente de virtualização do node (não reinicia o seu sistema operativo).
<p><b>Nota: </b>Nos servidores existentes sob o <em>node</em>, não há qualquer tipo de modificação.</p>
<a href="#help-left-panel-main"><div>Início</div></a>
<hr/>

<a id="help-left-panel-loadnode"><h1>Carregar <em><b>node</b></em></h1></a>
<p>Ao seleccionar esta opção é enviado um pedido ao <em>Central Management</em> para que o estado do <em>node</em> em questão seja actualizado.</p>
<a href="#help-left-panel-main"><div>Início</div></a>
<hr/>

<a id="help-left-panel-hostname"><h1>Alterar <em><b>Hostname</b></em></h1></a>
<p>A opção <em>Alterar Hostname</em> possibilita alterar o nome do servidor de virtualização.</p>
<p><b>Nota: </b>Esta opção apenas é disponibilizada para os servidores de virtualização, e não nas máquinas virtuais criadas nos <em>Nodes</em>.</p>
<a href="#help-left-panel-main"><div>Início</div></a>
<hr/>

<a id="help-left-panel-connectivity"><h1>Opções de conectividade</h1></a>
<p>Em <em>Opções de conectividade</em>, é possível editar a configuração da interface de gestão (<em>Management(</em>) ao
qual se encontra ligado o agente de virtualização.</p>
<p>É possível definir o endereço IP e o servidor de DNS.</p>
<p><b>Nota: </b>Esta opção apenas está disponível na versão <em>ETVA Enterprise</em>.</p>
<a href="#help-left-panel-main"><div>Início</div></a>
<hr/>

<a id="help-left-panel-keymap"><h1>Alterar <em><b>keymap</b></em></h1></a>
<p>Permite alterar a definição do <em>layout</em> do teclado, no <em>node</em> ou servidor seleccionado.</p>
<a href="#help-left-panel-main"><div>Início</div></a>
<hr/>

<a id="help-left-panel-status"><h1>Verificar o estado do <em>node</em></h1></a>
<p>Permite alterar a definição do <em>layout</em> do teclado, no servidor seleccionado.</p>
<a href="#help-left-panel-main"><div>Início</div></a>
<hr/>

<a id="help-left-panel-remove"><h1>Remover <em><b>node</b></em></h1></a>
<p>A opção <em>Remover node</em> remove um Node. Para concluir a opção basta responder afirmativamente à mensgem de confirmação.</p>
<p>Esta opção não remove o agente de virtualização</p>

<p><b>Nota 1: </b>Esta opção apenas remove o servidor de virtualização do <em>Central Management</em>,
eliminado a informação que este retém sobre ele.</p>
<p><b>Nota 2: </b>Nenhum volume de armazenamento é apagado.</p>
<a href="#help-left-panel-main"><div>Início</div></a>
<hr/>



<!-- ISO MANAGER -->

<a id="help-iso-main"><h1>Gestor de ISOs</h1></a>

<p>O gestor de ISOs é uma ferramenta que permite fazer a gestão das imagens a utilizar na criação de novas máquinas virtuais.
    No momento da criação das máquinas virtuais é possível seleccionar uma das imagens carregadas.</p>

<p>
O painel <em>Gestor de ISOs</em> é constituido por uma grelha central, que contém as imagens já carregadas, e pela barra de opções (acima).
As opções disponíveis são as seguintes:
</p>

<ul>
    <li><a href="#help-iso-upload"><em>Applet</em> de <em>Upload</em> (modo FTP)</a></li>
    <li><a href="#help-iso-download"><em>Download</em></a></li>
    <li><a href="#help-iso-rename">Renomear</a></li>
    <li><a href="#help-iso-remove">Apagar</a></li>
</ul>


<p><b>Nota: </b>O número de imagens admitido é limitado pelo espaço disponível no <em>Central Management</em>.</p>
<br/>
<hr/>

<a id="help-iso-upload"><h1><em><b>Applet</b></em> de <em><b>Upload</b></em> (modo FTP)</h1></a>
<p>
Para efectuar o envio de imagens para o <em>Central Management</em> deve seguir os seguintes passos:
</p>

<ol>
    <li>Seleccionar na barra superior, a opção <em>Applet de Upload (modo FTP)</em>;</li>
    <li>No aviso de seguranção seleccionar <em>Run</em>;</li>
    <li>Seleccionar a(s) imagem(ns) através da opção <em>Procurar</em>, encontrar os ficheiros, e de seguida carregar em <em>Open</em>;</li>
    <li>Conferir a lista de imagens a enviar (caso pretenda pode utilizar os botões de remoção para retirar ficheiros indesejados);</li>
    <li>Seleccionar a opção enviar (as imagens são copiadas para o <em>Central Management</em>).</li>
</ol>

<p><b>Nota 1: </b>Após o envio, as imagens aparecem listadas no painel <em>Gestor de ISOs</em>.</p>
<p><b>Nota 2: </b>Para executar a <em>applet</em> de envio é necessário ter instalado o <em>Java plug-in</em>.</p>
<a href="#help-iso-main"><div>Início</div></a>
<hr/>

<a id="help-iso-download"><h1><em><b>Download</b></em></h1></a>
<p>
A opção de <em>download</em> possibilita descarregar uma das imagens que se encontram no <em>Central Management</em>,
basta seleccionar a linha correspondente à imagem pretendida e seleccionar a opção de <em>download</em>.
</p>
<a href="#help-iso-main"><div>Início</div></a>
<hr/>

<a id="help-iso-rename"><h1>Renomear</h1></a>
<p>
Para modificar o nome de uma imagem que esteja no <em>Central Management</em>
selecciona-se a linha correspondente à imagem, e escolhe-se a opção <em>Renomear</em>.
De seguida surge uma janela onde é possível introduzir o novo nome. Proceder à alteração e <em>Guardar</em>.
</p>
<a href="#help-iso-main"><div>Início</div></a>
<hr/>

<a id="help-iso-remove"><h1>Apagar</h1></a>
<p>
Para apagar uma imagem que esteja no <em>Central Management</em>
selecciona-se a linha correspondente à imagem, e escolhe-se a opção <em>Apagar</em>.
Responder afirmativamente à mensagem de confirmação.
</p>
<a href="#help-iso-main"><div>Início</div></a>
<hr/>

<a id="help-bottom-panel-main"><h1>Painel de Informação</h1></a>
<p>O painel de informação pertence à tela principal do <em>Central Management</em> e
mostra o sucesso dos eventos de sistema.</p>

<hr/>