<?php
    // get translation module
    $layoutkit = $this->layoutkit;
    $words = $layoutkit->getWords();
    $model = $this->getModel();

    $page_url = PVars::getObj('env')->baseuri . implode('/', PRequest::get()->request);

    $formkit = $layoutkit->formkit;
    $callback_tag = $formkit->setPostCallback('GroupsController', 'createGroupCallback');
    
    $R = MOD_right::get();
    $GroupRight = $R->hasRight('Group');

    if ($redirected = $formkit->mem_from_redirect)
    {
        $Group_ = ((!empty($redirected->post['Group_'])) ? $redirected->post['Group_'] : '');
        $GroupDesc_ = ((!empty($redirected->post['GroupDesc_'])) ? $redirected->post['GroupDesc_'] : '');
        $Type = ((!empty($redirected->post['Type'])) ? $redirected->post['Type']: false);
        $problems = ((is_array($redirected->problems)) ? $redirected->problems : array());
    }
    else
    {
        $Group_ = '';
        $GroupDesc_ = '';
        $Type = false;
        $problems = array();
    }

?>
        <h3>Create a new Group</h3>
        <form method="POST" action="<?=$page_url ?>">
        <?=$callback_tag ?>
            <?= ((!empty($problems['General'])) ? $words->get('GroupsCreationFailed') : '' ); ?>
            <label for="name">Name:</label><?= ((!empty($problems['Group_'])) ? $words->get('GroupsCreationNameMissing') : '' ); ?><br />
            <input type="text" name="Group_" cols="60" rows="1" value='<?=$Group_?>'>
            <br /><br />
            <label for="description">Description:</label><?= ((!empty($problems['GroupDesc_'])) ? $words->get('GroupsCreationDescriptionMissing') : '' ); ?><br />
            <textarea name="GroupDesc_" cols="60" rows="5"><?=$GroupDesc_?></textarea><br /><br />
            <h3>Who can join</h3><?= ((!empty($problems['Type'])) ? $words->get('GroupsCreationTypeMissing') : '' ); ?>
            <ul>
                <li><input type="radio" name="Type" value="Public"<?= (($Type=='Public') ? ' checked': ''); ?>> Any BeWelcome member</li>
                <li><input type="radio" name="Type" value="Approved"<?= (($Type=='Approved') ? ' checked': ''); ?>> Any BeWelcome member, approved by moderators</li>
                <li><input type="radio" name="Type" value="Invited"<?= (($Type=='Invited') ? ' checked': ''); ?>> Only invited BeWelcome members</li>
            </ul>
            <h3>Create it now!</h3>
            <input type="submit" value="Create">
        </form> 

<?php
if (!$GroupRight >= 10) { echo 'You have no right to create groups, but hey, who cares ;)';}
?>
<?/**
<form method=post action=admingroups.php>
<input type=hidden name=IdGroup value=<?=$IdGroup?>>
<table>
<tr><td width=30%>Give the code name of the group as a word entry (must not exist in words table previously) like<br> <b>BeatlesLover</b> or <b>BigSausageEaters</b> without spaces !<br>
</td>
<td>
<input type=text
<?php
if ($Name != "")
  echo "readonly";   // don't change a group name because it is connected to words 
?>
 name=Name value="<?=$Name?>">
</td>
<tr><td>Give the group parent of this group</b><br>1 is the value for initial groups of first level</td>
<td>
<select name=IdParent>
<option value=1>Bewelcome Root</option>
<?php  for ($ii=0;$ii<count($TGroupList);$ii++) { ?>
  <option value="<?=$TGroupList[$ii]->id; ?>
<?php
if ($TGroupList[$ii]->id==$IdParent) echo " selected"; ?>
  >
  <?=$TGroupList[$ii]->Name?>:<?=ww("Group_".$TGroupList[$ii]->Name)?>
  </option>

<?php } ?>
</select>
<input type=text name=IdParent value="<?=$IdParent?>">
</td>

<tr><td width='30%'>Group name in English</td>
<td align=left><textarea name=Group_ cols=60 rows=1><?=$Group_?></textarea></td>
<tr><td>Group Description  (in English)</td>
<td align=left><textarea name=GroupDesc_ cols=60 rows=5><?=$GroupDesc_?></textarea></td>
<tr><td>Does this group has members ?</td>
<td>
<select name=HasMember>
<option value=HasMember
<?php
if ($HasMember == "HasMember")
    echo 'selected';
?>
>HasMember</option>
<option value=HasNotMember ";
<?php
if ($HasMember == "HasNotMember")
    echo 'selected';
?>
>HasNotMember</option>
</select>
</td>

<tr><td>Is this group public?</b></td>
<td>
<select name=Type>
<option value=Public
<?php
  if ($Type == "Public")
   echo 'selected';
?>
>Public</option>
<option value=NeedAcceptance
<?php
  if ($Type == "NeedAcceptance")
   echo 'selected';
?>
>NeedAcceptance</option>
</select>
</td>

<tr><td>Optional forum entry to associate with the group (more info)</td><td><input type=text name=MoreInfo value="<?=$MoreInfo?>"></td>
<tr><td>Optional picture to associate with the group (not yet available)</td><td><input type=text name=Picture value="<?=$Picture?>"></td>

<tr><td colspan=2 align=center>
<?php
  if ($IdGroup != 0)
  echo '<input type=submit id=submit name=submit value="update group">';
  else
  echo '<input type=submit id=submit name=submit value="create group">';
?>
<input type=hidden name=action value=creategroup>
</td>
</tr>
</table>
</form>
</center>
**/?>
