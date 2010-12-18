<table>
  <tbody>
    <tr>
      <th>Id:</th>
      <td><?php echo $etva_agent->getId() ?></td>
    </tr>
    <tr>
      <th>Server:</th>
      <td><?php echo $etva_agent->getServerId() ?></td>
    </tr>
    <tr>
      <th>Name:</th>
      <td><?php echo $etva_agent->getName() ?></td>
    </tr>
    <tr>
      <th>Description:</th>
      <td><?php echo $etva_agent->getDescription() ?></td>
    </tr>
    <tr>
      <th>Service:</th>
      <td><?php echo $etva_agent->getService() ?></td>
    </tr>
    <tr>
      <th>Ip:</th>
      <td><?php echo $etva_agent->getIp() ?></td>
    </tr>
    <tr>
      <th>State:</th>
      <td><?php echo $etva_agent->getState() ?></td>
    </tr>
    <tr>
      <th>Created at:</th>
      <td><?php echo $etva_agent->getCreatedAt() ?></td>
    </tr>
    <tr>
      <th>Updated at:</th>
      <td><?php echo $etva_agent->getUpdatedAt() ?></td>
    </tr>
  </tbody>
</table>

<hr />

<a href="<?php echo url_for('agent/edit?id='.$etva_agent->getId()) ?>">Edit</a>
&nbsp;
<a href="<?php echo url_for('agent/index') ?>">List</a>
