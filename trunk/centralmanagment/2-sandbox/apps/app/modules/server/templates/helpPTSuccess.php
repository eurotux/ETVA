<div class="help">
<div class="help-section">

    <a id="help-stats-server-load"><h1>Utilização do CPU</h1></a>
    <p>O gráfico de utilização do <em>node</em> ilustra a sua utilização em função do tempo.
        Nomeadamente, o número de processos à espera de aceder ao à unidade de processamento principal (<em>CPU</em>).</p>
    <p>A opção de <em>Iniciar/parar polling</em> (por baixo do gráfico), activa/inibe a actualização automática do gráfico.</p>
    <br/>
    <hr/>
</div>

<div class="help-section">
    <a id="help-stats-server-interface"><h1>Utilização das interfaces de rede</h1></a>
    <p>O gráfico de utilização do <em>node</em> ilustra a sua utilização das várias interfaces de rede em função do tempo.</p>
    <p>A opção de <em>Iniciar/parar polling</em> (por baixo do gráfico), activa/inibe a actualização automática do gráfico.</p>
    <br/>
    <hr/>
</div>

<div class="help-section">
    <a id="help-stats-server-mem"><h1>Utilização da memória</h1></a>
    <p>O gráfico de utilização do <em>node</em> ilustra a utilização da memória em função do tempo, nomeadamente a percentagem de espaço utilizada e o correspondente valor em <em>bytes</em>.</p>
    <p>A opção de <em>Iniciar/parar polling</em> (por baixo do gráfico), activa/inibe a actualização automática do gráfico.</p>
    <br/>
    <hr/>
</div>

<div class="help-section">
    <a id="help-stats-server-disk"><h1>Utilização dos discos</h1></a>
    <p>O gráfico de utilização do <em>node</em> ilustra a utilização dos discos existentes (escritas e leituras).</p>
    <p>A opção de <em>Iniciar/parar polling</em> (por baixo do gráfico), activa/inibe a actualização automática do gráfico.</p>
    <br/>
    <hr/>
</div>

<div class="help-section">
    <a id="help-stats-nodeload"><h1>Utilização do node</h1></a>
    <p>O gráfico de utilização do <em>node</em> ilustra a sua utilização em função do tempo.
        Nomeadamente, o número de processos à espera de aceder ao à unidade de processamento principal (<em>CPU</em>).</p>
    <p>A opção de <em>Iniciar/parar polling</em> (por baixo do gráfico), activa/inibe a actualização automática do gráfico.</p>
    <br/>
    <hr/>
</div>

<div class="help-section">
    <a id="help-stats-filter"><h1>Filtrar data</h1></a>
    <p>No painel filtrar data é possível efectuar duas operações:</p>
    <ol>
        <li><b>Alterar Intervalo(s)</b>: altera o eixo horizontal do(s) gráfico(s) abaixo, alterando o intervalo tempo.</li>
        <li><b>Gerar gráfico</b>: seleccionando um intervalo entre duas datas, <em>De</em> e <em>Até</em>, e escolhendo a opção <em>Gerar imagem do gráfico</em>,
        obtem-se uma imagem com os gráfico(s) correspondente(s).</li>
    </ol>
    <br/>
    <hr/>
</div>

<div class="help-section">
    <a id="help-vmachine-main"><h1>Opções para a gestão de servidores</h1></a>
    <p>Estão disponíveis as seguintes opções para a gestão de servidores virtuais</p>
    <ul>
        <li><a href="#help_virtual_machine_add">Criação de servidores</a></li>
        <li><a href="#help_vmachine_edit">Editar máquina virtual</a></li>
        <li><a href="#help-vmachine-remove">Remover máquina virtual</a></li>
        <li><a href="#help-vmachine-vnc">Abrir máquina virtual numa consola VNC</a></li>
        <li><a href="#help-vmachine-startstop">Iniciar/parar máquina virtual</a></li>
        <li><a href="#help-vmachine-migrate">Migrar máquina virtual</a></li>
    </ul>
    <br/>
    <hr/>


    <a id="help_virtual_machine_add"><h1>Assistente de criação de servidor</h1></a>
    <p>O assistente de criação de servidor possibilita a criação de máquinas virtuais.
    É constituido pelas seguintes etapas:</p>

    <ul>
        <li><a href="#help-virtial-machine-name">Nome</a></li>
        <li><a href="#help-virtial-machine-mem">Memória</a></li>
        <li><a href="#help-virtial-machine-cpu">Processador</a></li>
        <li><a href="#help-virtial-machine-storage">Armazenamento</a></li>
        <li><a href="#help-virtial-machine-network">Rede</a></li>
        <li><a href="#help-virtial-machine-start">Arranque</a></li>
        <li><a href="#help-virtial-machine-finalization">Finalização</a></li>
    </ul>
    <br/>
    <hr/>

    <a id="help-virtial-machine-name"><h2>Nome da máquina virtual:</h2></a>
    <p>Nesta etapa define-se o nome da máquina virtual e o tipo de sistema operativo.
    As opções do sistema operativo variam consoante a especificação do node:</p>
    <ul>
        <li>com XEN e suporte a virtualização por hardware:
            <ul>
                <li>Linux PV</li>
                <li>Linux HVM</li>
                <li>Windows</li>
            </ul>
        </li>
        <li>com XEN sem suporte de virtualização por hardware:
            <ul>
                <li>Linux PV</li>
            </ul>
        </li>
        <li>com KVM
            <ul>
                <li>Linux PV</li>
                <li>Linux HVM</li>
                <li>Windows</li>
            </ul>
        </li>
    </ul>
    <br/>

    <a href="#help_virtual_machine_add"><div>Início</div></a>
    <hr/>

    <a id="help-virtial-machine-mem"><h2>Memória:</h2></a>
    <p>Especificação da memória a ser usada pela máquina.</p>

    <a href="#help_virtual_machine_add"><div>Início</div></a>
    <hr/>

    <a id="help-virtial-machine-cpu"><h2>Processador:</h2></a>
    <p>Nesta etapa define-se o número de processadores a usar.</p>

    <a href="#help_virtual_machine_add"><div>Início</div></a>
    <hr/>

    <a id="help-virtial-machine-storage"><h2>Armazenamento:</h2></a>
    <p>Nesta etapa define-se o número de processadores a usar.</p>

    <ul>
        <li>usar um logical volume/ficheiro já existente - <em>Logical volume existente</em></li>
        <li>criar um novo logical volume/ficheiro (para criar um ficheiro através desta opção
            tem que se seleccionar o volume group <em>__DISK__</em> ) - <em>Novo logical volume</em>
        </li>
        <li>ou caso pretenda criar um ficheiro usar a opção <em>Novo ficheiro</em> que para tal necessita apenas do nome e tamanho.
        </li>
    </ul>

    <p><b>Nota </b>Se o node não suportar <em>physical volumes</em> a opção Logical volume existente
    será desabilitada, uma vez que não é possível criar <em>logical volumes</em>, mas
    sim apenas ficheiros.</p>

    <a href="#help_virtual_machine_add"><div>Início</div></a>
    <hr/>

    <a id="help-virtial-machine-network"><h2>Rede do servidor:</h2></a>
    <p>Especificação das interfaces de rede existentes no servidor. Caso não
    existam endereços MAC disponíveis é possível criar através de <em>Gestão da Pool</em> de
    <em>MAC</em>. Igualmente para as redes é possível criar nesta etapa através de <em>Adicionar
    rede</em>.</p>

    <a href="#help_virtual_machine_add"><div>Início</div></a>
    <hr/>

    <a id="help-virtial-machine-start"><h2>Arranque:</h2></a>
    <p>Especificação de parâmetros de arranque da máquina virtual. As opções nesta
    etapa variam consoante o tipo de sistema definido na etapa <a href="#help-virtial-machine-name"><em>Nome da máquina virtual</em></a>:</p>
    <ul>
        <li><em>Linux PV</em>
            <ul>
                <li>Instalação via rede. Url do kernel a carregar.</li>
            </ul>
        </li>
        <li><em>Outros</em>
            <ul>
                <li>Boot de rede (PXE)</li>
                <li>CD-ROM (ISO)</li>
            </ul>
        </li>
    </ul>

    <br/>
    <a href="#help_virtual_machine_add"><div>Início</div></a>
    <hr/>

    <a id="help-virtial-machine-finalization"><h2>Finalização:</h2></a>
    <p>Etapa final do assistente. Após confirmação da criação do servidor, os dados
    recolhidos nas etapas anteriores são processados e enviados ao servidor de virtualização.
    Posteriormente no painel <em>Servidores</em> poderá ser iniciada a máquina através da
    opção <em>Iniciar servidor</em>.</p>

    <a href="#help_virtual_machine_add"><div>Início</div></a>

    <!-- SERVER EDIT -->

    <hr/>
    <hr/>
</div>
<div class="help-section">
    <a id="help_vmachine_edit"><h1>Editar máquina virtual</h1></a>
    <p>A edição de uma máquina virtual permite a configuração de:</p>

    <ul>
        <li><a href="#help-vmachine-general">Opções gerais</a></li>
        <li><a href="#help-vmachine-net">Interfaces de rede</a></li>
        <li><a href="#help-vmachine-disks">Discos</a></li>
    </ul>
    <br/>
    <hr/>

    <a id="help-vmachine-general"><h2>Opções gerais:</h2></a>
    <p>Neste painel é permitido alterar o nome, memória, opções do keymap e
    parâmetros de boot da máquina. Os parâmetros de boot variam consoante o tipo da
    máquina virtual.</p>
    <a href="#help_vmachine_edit"><div>Início</div></a>
    <hr/>

    <a id="help-vmachine-net"><h2>Interfaces de rede:</h2></a>
    <p>Adicionar/remover interfaces. É possível alterar o tipo de driver a usar (se a máquina virtual for HVM ou KVM).</p>
    <a href="#help_vmachine_edit"><div>Início</div></a>
    <hr/>

    <a id="help-vmachine-disks"><h2>Discos:</h2></a>
    <p>A máquina virtual tem que ter pelo menos um disco associado. Para adicionar/remover discos selecciona-se o disco pretendido
    e recorre-se ao drag-n-drop entre as tabelas.


        Adicionar/remover interfaces. É possível alterar o tipo de driver a usar (se a máquina virtual for HVM ou KVM).</p>
    <p><b>Nota: </b>O disco de boot da máquina é o disco que se encontra na primeira posição da tabela.</p>
    <a href="#help_vmachine_edit"><div>Início</div></a>
    <hr/>

    <!-- REMOVE SERVER -->
    <a id="help-vmachine-remove"><h1>Remover máquina virtual</h1></a>
    <p>Para remover um servidor, selecciona-se a máquina a remover e clica-se em <em>Remover servidor.</em></p>
    <p>A opção <em>Manter disco</em> permite manter o disco associado à máquina aquando da sua criação, caso contrário será também removido.</p>
    <a href="#help-vmachine-main"><div>Início</div></a>
    <hr/>

    <!-- VNC -->
    <a id="help-vmachine-vnc"><h1>Abrir máquina virtual numa consola VNC</h1></a>
    <p>Seleccionando um servidor e de seguida clicando em <em>Abrir numa consola</em> é possível estabelecer uma ligação VNC com a máquina, desde que esta esteja a correr.</p>
    <p><b>Nota: </b>Caso o teclado esteja desconfigurado é possível alterar o <em>keymap</em> do VNC através da opção <em>Alterar keymap</em> no sub-menu de contexto do painel <em>Nodes</em>.
    O <em>keymap</em> pode ser definido quer ao nível de cada servidor, ou definir um <em>keymap</em> de uso geral, o qual será usado por omissão na criação de novas máquinas
    virtuais.
    </p>
    <a href="#help-vmachine-main"><div>Início</div></a>
    <hr/>

    <!-- START STOP -->
    <a id="help-vmachine-startstop"><h1>Iniciar/parar máquina virtual</h1></a>
    <p>No arranque da máquina virtual é possível escolher um dos seguintes parâmetro de boot:</p>
    <p><b>Disco: </b>Arranque pelo disco associado ao servidor.</p>
    <p><b>PXE: </b>Arranque por PXE (Só disponível caso o tipo da máquina virtual <b>não seja</b> <em>Linux PV</em>).</p>
    <p><b>Location URL: </b>Arranque pelo url definido em Location (Só disponível caso o tipo da máquina virtual <b>seja</b> <em>Linux PV</em>
    ).</p>
    <p><b>CD-ROM: </b>Arranque pela imagem montada no CD-ROM (Só disponível caso o tipo da máquina virtual <b>não seja</b> <em>Linux PV</em>).</p>
    <a href="#help-vmachine-main"><div>Início</div></a>
    <hr/>

    <!-- MIGRAR MÁQUINA VIRTUAL -->
    <a id="help-vmachine-migrate"><h1>Migrar máquina virtual</h1></a>
    <p>
    Seleccionando um servidor e de seguida clicando em <em>Migrar servidor</em> é possível migrar uma máquina de um <em>node</em> para outro desde que partilhem o mesmo armazenamento. A migração de uma máquina virtual é efectuada no modo <em>offline</em>.
    </p>
    <p><b>Nota: </b>Esta opção só está disponível no modelo <em>ETVM</em>.
    </p>
    <a href="#help-vmachine-main"><div>Início</div></a>
    <hr/>
</div>
</div>
