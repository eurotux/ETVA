<table>
  <tbody>
    <tr>
      <th>Id:</th>
      <td><?php echo $etva_node->getId() ?></td>
    </tr>
    <tr>
      <th>Name:</th>
      <td><?php echo $etva_node->getName() ?></td>
    </tr>
    <tr>
      <th>Memtotal:</th>
      <td><?php echo $etva_node->getMemtotal() ?></td>
    </tr>
    <tr>
      <th>Cputotal:</th>
      <td><?php echo $etva_node->getCputotal() ?></td>
    </tr>
    <tr>
      <th>Ip:</th>
      <td><?php echo $etva_node->getIp() ?></td>
    </tr>
    <tr>
      <th>Network cards:</th>
      <td><?php echo $etva_node->getNetworkCards() ?></td>
    </tr>
    <tr>
      <th>State:</th>
      <td><?php echo $etva_node->getState() ?></td>
    </tr>
    <tr>
      <th>Created at:</th>
      <td><?php echo $etva_node->getCreatedAt() ?></td>
    </tr>
  </tbody>
</table>

<hr />

<a href="<?php echo url_for('node/edit?id='.$etva_node->getId()) ?>">Edit</a>
&nbsp;
<a href="<?php echo url_for('node/index') ?>">List</a>
