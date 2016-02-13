<?php
$active = 'data';
$head = '<link rel="stylesheet" href="//cdn.bootcss.com/webui-popover/1.2.5/jquery.webui-popover.min.css">
<script src="//cdn.bootcss.com/webui-popover/1.2.5/jquery.webui-popover.min.js"></script>';
require_once 'header.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$start = isset($_GET['loc']) ? (int)$_GET['loc'] : 0;
$piece = isset($_GET['piece']) ? (int)$_GET['piece'] : 20;
switch ($action)
{
  case 'rejudge':
  case 'del':
    if($action == 'del')
    {
      $sid = $_GET['sid'];
      $sql = 'DELETE FROM `mo_judge_solution` WHERE `id` = ?';
      $db->prepare($sql);
      $db->bind('i', $sid);
      $db->execute();
      $msg = '成功删除评测记录#'. $sid. '。';
    }
    else
    {
        $sid = $_GET['sid'];
        $sql = 'UPDATE `mo_judge_solution` SET `state` = 0 WHERE `id` = ?';
        $db->prepare($sql);
        $db->bind('i', $sid);
        $db->execute();
        $msg = '提交#'. $sid. '将很快再次评测。';
    }
	default:
	case 'list':
    $sql = 'SELECT `id`, `pid`, `uid`, `client`, `post_time`, `state`, `language`, `code_length`,'.
    " `used_time`, `used_memory` FROM `mo_judge_solution` ORDER BY `id` DESC LIMIT $start,$piece";
		$db->prepare($sql);
		$result = $db->execute();
		$solution_count = mo_get_solution_count();
		break;
	case 'search':
    $sql = 'SELECT `id`, `pid`, `uid`, `client`, `post_time`, `state`, `language`, `code_length`,'.
    " `used_time`, `used_memory` FROM `mo_judge_solution` WHERE 1=1";
		if (isset($_GET['sid']) && $_GET['sid'])
		{
			$sql .= ' AND `id` = '. $db->clean($_GET['sid']);
		}
		if (isset($_GET['state']) && $_GET['state'])
		{
			$sql .= ' AND `state` = '. $db->clean($_GET['state']);
		}
		$sql .= " ORDER BY `id` DESC LIMIT $start,$piece";
		$db->prepare($sql);
		$result = $db->execute();
		$solution_count = count($result);
}
$page = ceil($solution_count / $piece);
?>
<div class="container">
    <?php if (!$result) echo '<div class="alert alert-warning">评测记录暂时为空！还没有人提交评测。</div>'; ?>
    <?php if (isset($msg)) echo '<div class="alert alert-success">'.$msg.'</div>'; ?>
    <div class="col-md-3">
        <form method="get" action="view_solution.php">
          <h4>快速查看</h4>
            <div class="input-group">
             <span class="input-group-addon">#</span>
             <input type="text" name="sid" class="form-control" placeholder="评测编号">
                <span class="input-group-btn">
                  <button class="btn btn-default" type="submit" >
                     VIEW
                </button>
               </span>
          </div>
        </form>
        <h4>筛选器</h4>
        <form method="get" action="solution.php">
  		  	<input type="hidden" name="action" value="search">
          <div class="input-group">
             <span class="input-group-addon">#</span>
             <input type="text" name="pid" class="form-control" placeholder="题号" value="<?php if(isset($_GET['pid'])) echo $_GET['pid'];?>">
          </div>
					<div class="input-group">
               <span class="input-group-addon">@</span>
	             <input type="text" name="user" class="form-control" placeholder="用户" value="<?php if(isset($_GET['user'])) echo $_GET['user'];?>">
	        </div>
          <label class="checkbox-inline">
             <input type="radio" name="user_type" id="user_type_name"
                value="username"<?php if(!isset($_GET['user_type'])||$_GET['user_type']!='uid')echo ' checked';?>> 用户名
          </label>
          <label class="checkbox-inline">
             <input type="radio" name="user_type" id="user_type_id"
                value="uid"<?php if(isset($_GET['user_type'])&&$_GET['user_type']=='uid')echo ' checked';?>> 用户ID
          </label>
          <div class="form-group">
             <select name="state" class="form-control">
              <option value="">状态不限</option>
              <option value="10"<?php if(isset($_GET['state'])&&$_GET['state']=='10')echo ' selected';?>>Accepted</option>
              <option value="6"<?php if(isset($_GET['state'])&&$_GET['state']=='6')echo ' selected';?>>Wrong Answer</option>
              <option value="4"<?php if(isset($_GET['state'])&&$_GET['state']=='4')echo ' selected';?>>Runtime Error</option>
              <option value="1"<?php if(isset($_GET['state'])&&$_GET['state']=='1')echo ' selected';?>>Compile Error</option>
              <option value="2"<?php if(isset($_GET['state'])&&$_GET['state']=='2')echo ' selected';?>>Memory Limit Exceed</option>
              <option value="3"<?php if(isset($_GET['state'])&&$_GET['state']=='3')echo ' selected';?>>Time Limit Exceed</option>
             </select>
          </div>
					<button class="btn btn-default pull-right" type="submit" ><span class="glyphicon glyphicon-search"></span>搜索</button>
        </form>
    </div>
    <div class="col-md-9">
        <div class="row">
          <table class="table table-striped table-hover">
           <tbody>
            <?php
            $detail = array();
			foreach ($result as $solution)
			{
				$detail[$solution['id']] = json_encode($solution);
				$tr = (isset($_GET['pid']) && (string)$solution['id'] == $_GET['pid']) ? '<tr class="success">' : '<tr>';
				echo '
				'.$tr.'
				 <td>'.$solution['id'].'</td>
 				 <td>'.$solution['uid'].'</td>
 				 <td>'.$solution['pid'].'</td>
 				 <td class="hidden-xs">'.$solution['language'].'</td>
 				 <td class="hidden-xs">'.$solution['code_length'].'</td>
 				 <td>'.$solution['state'].'</td>
 				 <td class="hidden-xs">'.$solution['used_time'].'</td>
 				 <td class="hidden-xs">'.$solution['used_memory'].'</td>
				 <td>
				 <a class="btn btn-primary btn-sm" onClick="location.href=\'edit_problem.php?action=edit&pid='.$solution['id'].'\'">编辑</a>
				 <button id="'.$solution['id'].'detail" type="button" class="btn btn-info btn-sm" onclick="prob_detail('.$solution['id'].')">详情</button>
				 <button type="button" class="btn btn-danger btn-sm" onclick="del_solution('. $solution['id']. ')">删除</button>
				 </tr>';
			}
            ?>
           </tbody>
           <thead>
            <tr>
             <th>#</th>
             <th>提交者</th>
             <th>题目</th>
             <th class="hidden-xs">语言</th>
             <th class="hidden-xs">代码长度</th>
             <th>状态</th>
             <th class="hidden-xs">运行时间</th>
             <th class="hidden-xs">使用内存</th>
             <th>操作</th>
            </tr>
           </thead>
          </table>
            <ul class="pager">
              <li class="<?php echo $start >= $piece ? 'previous' : 'previous disabled';?>"><a href="<?php echo $start >= $piece ? 'solution.php?loc='.($start-$piece) : '#';?>">&larr; 上一页</a></li>
              共<?php echo ceil($solution_count / $piece); ?>页，正在浏览第<?php echo ceil($start / $piece) + 1; ?>页
              <li class="<?php echo $solution_count - $start >= $piece ? 'next' : 'next disabled';?>"><a href="<?php echo $start + $piece < $solution_count ? 'solution.php?loc='.($start+$piece) : '#';?>">下一页 &rarr;</a></li>
            </ul>
         </div>
    </div>
</div>
<div class="modal fade" id="del_solution" tabindex="-1" role="dialog"
   aria-labelledby="myModalLabel" aria-hidden="true">
   <form id="delform" role="form" method="get" action="solution.php" enctype="multipart/form-data">
	   <div class="modal-dialog">
		  <div class="modal-content">
			 <div class="modal-header">
				<button type="button" class="close"
				   data-dismiss="modal" aria-hidden="true">
					  &times;
				</button>
				<h4 class="modal-title" id="del_solution_title">

				</h4>
			 </div>
			 <div class="modal-body">
				删除后本题目的数据将会消失，但相关的提交、讨论、文件、用户记录不会被删除。
			 </div>
			 <div class="modal-footer">
				 <input type="hidden" name="action" value="del">
				 <input type="hidden" id="del_sid" name="sid" value="0">
				<button type="button" class="btn btn-default"  data-dismiss="modal">
					取消
				</button>
				<button type="submit" class="btn btn-danger">删除</button>
			 </div>
		  </div>
      </form>
</div>
<script>
function del_solution(pid) {
	$('#del_solution_title').html('<span class="glyphicon glyphicon-warning-sign"></span> 删除评测#'+pid);
	$('#del_confirm').remove();
    $('#del_sid').val(sid);
	$('.modal').modal();
}
</script>
<?php
if ($detail)
{
	echo "<script>\nsolution = new Array();\n";
	foreach ($detail as $sid => $solution)
	{
		echo 'solution[\''.$sid.'\'] = '.$solution.";\n";
	}
	echo "</script>\n";
}
require_once 'footer.php';
