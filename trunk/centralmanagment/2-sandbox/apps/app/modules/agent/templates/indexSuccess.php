<h1>Agent List</h1>

<table>
  <thead>
    <tr>
      <th>Id</th>
      <th>Server</th>
      <th>Name</th>
      <th>Description</th>
      <th>Service</th>
      <th>Ip</th>
      <th>State</th>
      <th>Created at</th>
      <th>Updated at</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($etva_agent_list as $etva_agent): ?>
    <tr>
      <td><a href="<?php echo url_for('agent/show?id='.$etva_agent->getId()) ?>"><?php echo $etva_agent->getId() ?></a></td>
      <td><?php echo $etva_agent->getServerId() ?></td>
      <td><?php echo $etva_agent->getName() ?></td>
      <td><?php echo $etva_agent->getDescription() ?></td>
      <td><?php echo $etva_agent->getService() ?></td>
      <td><?php echo $etva_agent->getIp() ?></td>
      <td><?php echo $etva_agent->getState() ?></td>
      <td><?php echo $etva_agent->getCreatedAt() ?></td>
      <td><?php echo $etva_agent->getUpdatedAt() ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

  <a href="<?php echo url_for('agent/new') ?>">New</a>
