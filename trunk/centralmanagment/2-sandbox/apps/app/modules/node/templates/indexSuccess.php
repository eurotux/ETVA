<h1>Node List</h1>

<table>
  <thead>
    <tr>
      <th>Id</th>
      <th>Name</th>
      <th>Memtotal</th>
      <th>Cputotal</th>
      <th>Ip</th>
      <th>Network cards</th>
      <th>State</th>
      <th>Created at</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($etva_node_list as $etva_node): ?>
    <tr>
      <td><a href="<?php echo url_for('node/show?id='.$etva_node->getId()) ?>"><?php echo $etva_node->getId() ?></a></td>
      <td><?php echo $etva_node->getName() ?></td>
      <td><?php echo $etva_node->getMemtotal() ?></td>
      <td><?php echo $etva_node->getCputotal() ?></td>
      <td><?php echo $etva_node->getIp() ?></td>
      <td><?php echo $etva_node->getNetworkCards() ?></td>
      <td><?php echo $etva_node->getState() ?></td>
      <td><?php echo $etva_node->getCreatedAt() ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

  <a href="<?php echo url_for('node/new') ?>">New</a>
