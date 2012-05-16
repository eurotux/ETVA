<div class="help">
<div class="help-section">
    <a id="ovf_import"><h1>Importar OVF</h1></a>
    O assistente de importação OVF (Open Virtualization Format) permite importar para a appliance uma máquina virtual descrita numa ficheiro OVF.

    O formato OVF descreve os metadados de uma máquina virtual permitindo criar um pacote com determinada máquina virtual.
    O assistente de importação OVF é constituído pelas seguintes etapas:<br /><br />
    <ul>
        <li><b>Ficheiro OVF de origem -</b> Nesta etapa especifica-se o ficheiro OVF a importar.<br>
            O URL do ficheiro tem que estar acessível via endereço web para que o CM consiga importa-lo.
        </li>
        <li><b>Resumo do OVF -</b> Detalhes do ficheiro OVF. Disponibiliza informação acerca do produto, versão, tamanho total dos ficheiros referenciados pelo pacote OVF, se disponível.
        </li>
        <li><b>Contrato de licença -</b> Se especificado no ficheiro OVF, esta etapa surgirá com o EULA. Caso contrário, esta etapa será omitida.
        </li>
        <li><b>Nome e localização -</b> Nesta etapa define-se o nome da máquina virtual, o node de destino e o tipo de sistema operativo. As opções do sistema operativo variam consoante a especificação do node:
            <ul style="list-style-type:square">
                <li>com XEN e suporte a virtualização por hardware:
                    <ul style="list-style-type:none">
                        <li>- Linux PV</li>
                        <li>- Linux HVM</li>
                        <li>- Windows</li>
                    </ul>
                </li>
                <li>com XEN sem suporte de virtualização por hardware:
                    <ul style="list-style-type:none">
                        <li>- Linux PV</li>
                    </ul>
                </li>
                <li>com KVM
                    <ul style="list-style-type:none">
                        <li>- Linux</li>
                        <li>- Windows</li>
                    </ul>
                </li>
            </ul>
            Antes de prosseguir para a próxima etapa, o assistente verifica se existe memória disponível para criar a
            máquina e se os drivers para os discos e para as interfaces de rede mencionados no OVF são suportados pelo servidor de virtualização escolhido.
    <br><br>
            Os drivers dos discos suportados para máquinas XEN com ou sem virtualização por hardware são: ide, xen e scsi. Nas máquinas KVM os drivers são: ide, virtio e scsi.
    <br><br>
            Os drivers da placa de rede suportados para máquinas em HVM ou KVM são: e1000, rtl8139 e virtio. Numa máquina XEN sem suporte a virtualização nao suporta drivers.
    <br><br>
            Caso o servidor de virtualização escolhido não possua memória disponível suficiente ou não suporte os drivers mencionados no OVF a importação não poderá ser efectuada.
        </li>
        <li><b>Armazenamento -</b> Nesta etapa é efectuado o mapeamento dos discos no node. É possível especificar o nome a dar ao logical volume bem como definir o volume group.
            É necessário que todo os discos sejam mapeados para prosseguir para a próxima etapa.
        </li>
        <li><b>Interfaces de rede -</b> Nesta etapa é efectuado o mapeamento das interfaces de rede. É possível especificar novas interfaces de rede.
            É necessário que todas as interfaces de rede sejam mapeadas para prosseguir para a próxima etapa.
        </li>
        <li><b>Finalização -</b> Etapa final do assistente. Após confirmação da importação da máquina virtual, os dados recolhidos nas etapas anteriores são processados e enviados ao servidor de virtualização.
        </li>
    </ul>
    <br>
    <hr>
    <br>
</div>
<div class="help-section">
    <a id="ovf_export"><h1>Exportar OVF</h1></a>
    A exportação de um OVF permite obter a configuração de uma máquina virtual no formato OVF (Open Virtualization Format).
    <br><br><b>Para efectuar esta operação é necessário que a máquina virtual a exportar esteja parada.</b>
    <br><br>Este formato gera uma colecção de ficheiros que são referenciados por um ficheiro de extensão .ovf contendo os metadados dessa máquina virtual.
    O ficheiro gerado vem no formato OVA (Open Virtualization Archive), que consiste simplesmente num arquivo contendo os ficheiros gerados na criação do OVF.
</div>
</div>
