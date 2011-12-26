<?php
/*

Copyright (c) 2007 BeVolunteer

This file is part of BW Rox.

BW Rox is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

BW Rox is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, see <http://www.gnu.org/licenses/> or
write to the Free Software Foundation, Inc., 59 Temple Place - Suite 330,
Boston, MA  02111-1307, USA.

*/

    $rights = new MOD_right;
    $rights->HasRight('Comments');

    // Index the written comments array by memberId
    $comments_written_array = array();
    foreach ($comments_written as $comment) {
        // echo 'Comment '.$comment->id.':  --------- ';
        $comments_written_array[$comment->IdToMember] = $comment;
    }    

    $iiMax = (isset($max) && count($comments) > $max) ? $max : count($comments);
    $tt = array ();
    for ($ii = 0; $ii < $iiMax; $ii++) {
        $c = $comments[$ii];
        $quality = "neutral";
        if ($c->comQuality == "Good") {
            $quality = "good";
        }
        if ($c->comQuality == "Bad") {
            $quality = "bad";
        }

        $tt = explode(",", $comments[$ii]->Lenght);

        // Check if there's a counter comment available:
        $cc = false;
        if (isset($comments_written_array[$c->IdFromMember])) {
            $cc = $comments_written_array[$c->IdFromMember];
        }
?>

  <div class="subcolumns profilecomment">

    <div class="c75l" >
      <div class="subcl" >
        <a href="members/<?=$c->Username?>">
           <img class="float_left framed"  src="members/avatar/<?=$c->Username?>/?xs"  height="50px"  width="50px"  alt="Profile" />
        </a>
        <div class="comment">
            <p class="floatbox">
              <? if ($this->loggedInMember && $c->IdFromMember == $this->loggedInMember->Id) echo '<a href="members/'.$this->member->username.'/comments/add" title="Edit">'.$ww->edit.'</a>' ?>
              <strong class="<?=$quality?>"><?=$c->comQuality?></strong><br/>
              <span class="small grey"><?=$words->get('CommentFrom','<a href="members/'.$c->Username.'">'.$c->Username.'</a>')?> - <?=$c->created?></span>
            </p>
            <p>
              <em><?=$c->TextWhere?></em>
            </p>
            <p>
              <?=$c->TextFree?>
            </p>
            <p>
              <em class="small"><?=$words->get('CommentLastUpdated')?>: <?=$layoutbits->ago($c->unix_updated)?></em>
            </p>
        </div> <!-- comment -->
      </div> <!-- subcl -->
    </div> <!-- c75l -->
    <div class="c25r" >
      <div class="subcr" >
        <ul class="linklist" >
            <li>
                <?php
                    for ($jj = 0; $jj < count($tt); $jj++) {
                        if ($tt[$jj]=="") continue; // Skip blank category comment : todo fix find the reason and fix this anomaly
                        echo "                    <li>", $words->get("Comment_" . $tt[$jj]), "</li>\n";
                    }
                ?>
            </li>
            <li>
                <?php if ($this->loggedInMember) :?> <a href="members/reportcomment/<?php echo $this->member->Username;?>/<?php echo $comments[$ii]->id;?>" ><?=$words->get('ReportCommentProblem')?><?php endif;?></a>
            </li>
            <li>
            <?php if (MOD_right::get()->HasRight('Comments'))  { ?>
                <a href="bw/admin/admincomments.php?action=editonecomment&IdComment=<?php echo $comments[$ii]->id; ?>"><?=$words->get('EditComment')?></a>
                <?php
                };
                ?>
            </li>
        </ul>
      </div> <!-- subcr -->
    </div> <!-- c25r -->
  </div> <!-- subcolumns -->
  <?php if ($cc && $quality = $cc->comQuality) : ?> 
  <div class="profilecomment floatbox counter">
      <div class="subcolumns profilecomment">

        <div class="c75l" >
          <div class="subcl" >
            <a href="members/<?=$cc->Username?>">
               <img class="float_left framed"  src="members/avatar/<?=$cc->Username?>/?xs"  height="50px"  width="50px"  alt="Profile" />
            </a>
            <div class="comment">
                <p class="floatbox">
                  <? if ($this->loggedInMember && $cc->IdFromMember == $this->loggedInMember->Id) echo '<a href="members/'.$this->member->username.'/comments/add" title="Edit">'.$ww->edit.'</a>' ?>
                  <strong class="<?=$cc->comQuality?>"><?=$cc->comQuality?></strong><br/>
                  <span class="small grey"><?=$words->get('CommentFrom','<a href="members/'.$cc->Username.'">'.$cc->Username.'</a>')?> - <?=$cc->created?></span>
                </p>
                <p>
                  <em><?=$cc->TextWhere?></em>
                </p>
                <p>
                  <?=$cc->TextFree?>
                </p>
                <p>
                  <em class="small"><?=$words->get('CommentLastUpdated')?>: <?=$layoutbits->ago($cc->unix_updated)?></em>
                </p>
            </div> <!-- comment -->
          </div> <!-- subcl -->
        </div> <!-- c75l -->
        <div class="c25r" >
          <div class="subcr" >
            <ul class="linklist" >
                <li>
                    <?php
                        for ($jj = 0; $jj < count($tt); $jj++) {
                            if ($tt[$jj]=="") continue; // Skip blank category comment : todo fix find the reason and fix this anomaly
                            echo "                    <li>", $words->get("Comment_" . $tt[$jj]), "</li>\n";
                        }
                    ?>
                </li>
                <li>
                    <?php if ($this->loggedInMember) :?> <a href="members/reportcomment/<?php echo $this->member->Username;?>/<?php echo $cc->id;?>" ><?=$words->get('ReportCommentProblem')?><?php endif;?></a>
                </li>
                <li>
                <?php if (MOD_right::get()->HasRight('Comments'))  { ?>
                    <a href="bw/admin/admincomments.php?action=editonecomment&IdComment=<?php echo $cc->id; ?>"><?=$words->get('EditComment')?></a>
                    <?php
                    };
                    ?>
                </li>
            </ul>
          </div> <!-- subcr -->
        </div> <!-- c25r -->
      </div> <!-- subcolumns -->
  </div> <!-- profilecomment counter -->
  <?php endif; ?>
  <?=($ii == $iiMax-1) ? '' : '<hr/>' ?>

<?php
}
