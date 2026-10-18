<!--if:start key="html"--><!--value--><!--else-->
<!--if:start key="hidden"--><input type="hidden" name="<!--name|html-->" value="<!--value|html-->"><!--else-->
<label class="adm-field"><span><!--label|html--></span>
<!--if:start key="textarea"--><textarea name="<!--name|html-->" rows="<!--rows|html-->" <!--required_attr-->><!--value|html--></textarea><!--else-->
<!--if:start key="select"--><select name="<!--name|html-->" <!--required_attr-->><!--loop:start key="options"--><option value="<!--options_value|html-->" <!--options_selected-->><!--options_label|html--></option><!--loop:end--></select><!--else-->
<!--if:start key="checkbox"--><input type="hidden" name="<!--name|html-->" value="0"><input type="checkbox" name="<!--name|html-->" value="1" <!--checked--> <!--required_attr-->><!--else-->
<input type="<!--input_type|html-->" name="<!--name|html-->" <!--if:start key="file"--><!--else-->value="<!--value|html-->"<!--if:end--> <!--required_attr--> autocomplete="off">
<!--if:end--><!--if:end--><!--if:end-->
<small><!--help|html--></small></label>
<!--if:end--><!--if:end-->
