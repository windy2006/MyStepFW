<?php
$path = PATH.'data/upload/';
$upload = new myUploader($path, true);
$upload->do();
echo $upload->getResult();
?>
<form action="" method="post" enctype="multipart/form-data">
    <input type="file" name="anyname[]" /><br /><br />
    <input type="file" name="anyname[]" /><br /><br />
    <input type="file" name="anyname[]" /><br /><br />
    <button type="submit" class="but1">上传</button>
</form>
