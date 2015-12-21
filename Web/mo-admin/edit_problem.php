<?php
$active = 'problem';
$head = '<link rel="stylesheet" href="https://pandao.github.io/editor.md/css/editormd.min.css" />
<script src="inc/bootstrap.file-input.js"></script>
<script src="inc/admin.js"></script>
<script>
	$().ready(function() {
		filePrint();
	});
</script>';
require_once 'header.php';
if (!isset($_GET['action']))
{
	$error = True;
}
else
{
	switch ($_GET['action'])
	{
		case 'edit':
			$pv_info = get_problem($_GET);
			if (!$pv_info)
			{
				$error = True;
				break;
			}
		case 'add':
			if (isset($_SESSION['publish_tmp']))
			{
				$pv_info = $_SESSION['publish_tmp'];
			}
			$error = False;
			break;
		default:
			$error = True;
	}
}
if (!$error)
{
?>
<div class="container">
<ul class="nav nav-tabs">
<li><a href="problem.php">管理题目</a></li>
<li<?php if ($_GET['action'] == 'add') echo ' class="active"'; ?>><a href="edit_problem.php?action=add">添加题目</a></li>
</ul>
<form role="form" method="post" action="publish.php">
	<div class="form-group input-group-lg">
	 <label class="control-label" for="title"><h2>标题</h2></label>
	 <input id="title" class="form-control" type="text" name="title" placeholder="请在此输入标题～"<?php echo isset($pv_info)?' value="'.$pv_info['title'].'"':''; ?>>
	</div>
<div id="test-editormd">
	<textarea style="display:none;">
	</textarea>
</div>
<script src="https://pandao.github.io/editor.md/editormd.min.js"></script>
<script type="text/javascript">
	var testEditor;
	$(function() {
		$.get('inc/instruction.md', function(md){
			testEditor = editormd("test-editormd", {
				width: "100%",
				height: 740,
				path : '//cdn.rawgit.com/pandao/editor.md/master/lib/',
				markdown : <?php echo isset($pv_info)?'\''.$pv_info['description'].'\'':'md'; ?>,
				codeFold : true,
				saveHTMLToTextarea : true,
				searchReplace : true,
				htmlDecode : "style,script,iframe|on*",
				emoji : true,
				taskList : true,
				tocm            : true,
				tex : true,
				flowChart : true,
				sequenceDiagram : true,
				imageUpload : true,
				imageFormats : ["jpg", "jpeg", "gif", "png", "bmp", "webp"],
				imageUploadURL : "/mo-includes/upload.php",
				onload : function() {
					console.log('onload', this);
				}
			});
		});
	});
</script>
	<div class="form-group input-group-sm">
	 <h3>测试数据</h3>
	 <div class="test-data">
		 <?php if (isset($pv_info)) echo '<div class="alert alert-warning">如不修改测试数据，请不要在此选择文件！一旦选择文件，将覆盖此题之前版本所有测试数据！</div>'; ?>
		 <div class="row">
			<div class="col-md-3">
			 <p><input type="file" id="input1" title="输入数据 #1" name="input1" class="btn-primary"></p>
			 </div>
			 <div class="col-md-3">
			 <p><input type="file" id="stdout1" title="输出数据 #1" name="stdout1" class="btn-primary"></p>
			 </div>
		 </div>
	 </div>
	 <button type="button" class="btn btn-danger" onclick="add_test_data()">添加一组测试数据</button>
	</div>
	<div class="form-group input-group-sm">
		<div class="row">
			<div class="col-md-3">
				<label class="control-label" for="time_limit"><h3>时间限制（ms）</h3></label>
				<input id="time_limit" class="form-control" type="text" name="time_limit" placeholder="单位：毫秒" pattern="^[0-9]*$"<?php echo isset($pv_info)?' value="'.$pv_info['time_limit'].'"':''; ?>>
			</div>
			<div class="col-md-3">
				<label class="control-label" for="time_limit"><h3>内存限制（MB）</h3></label>
				<input id="memory_limit" class="form-control" type="text" name="memory_limit" placeholder="单位：兆字节" pattern="^[0-9]*$"<?php echo isset($pv_info)?' value="'.$pv_info['memory_limit'].'"':''; ?>>
			</div>
			<div class="col-md-6">
				<label class="control-label" for="tag"><h3>标签</h3></label>
				<input id="tag" class="form-control" type="text" name="tag" placeholder="多个标签使用空格分开"<?php echo isset($pv_info)?' value="'.$pv_info['tag'].'"':''; ?>>
			</div>
		</div>
	</div>
	<div class="form-group input-group-sm">
	 <label class="control-label" for="extra"><h3>额外信息</h3></label>
	 <input id="extra" class="form-control" type="text" name="extra" placeholder="TODO">
	</div>
	<?php
	if ($_GET['action'] == 'add')
	{
		echo '<input type="hidden" name="action" value="add">';
	}
	elseif ($_GET['action'] == 'edit')
	{
		echo '<input type="hidden" name="action" value="edit">';
		echo '<input type="hidden" name="edit_id" value="'. $pv_info['id']. '">';
	}
	?>
	<button type="submit" class="btn btn-default btn-lg">发布</button>
</form>
</div>
<?php
}
else
{
?>
<div class="container">
	<div class="alert alert-danger">未定义的操作。</div>
</div>
<?php
}
require_once 'footer.php';