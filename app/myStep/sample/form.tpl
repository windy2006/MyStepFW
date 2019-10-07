<div class="card w-100 mb-5 mb-sm-2">
	<div class="card-body p-0 table-responsive">
		<table class="table table-striped table-hover m-0 font-sm">
			<tr>
				<td>
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text">自动填充</span>
						</div>
						<input id="auto_fill" name="country" class="form-control" type="text" placeholder="中国" value="" />
						<div class="input-group-append">
							<select id="auto_mode" class="custom-select">
								<option value="country" default="中国">国家</option>
								<option value="province" default="北京">省市</option>
								<option value="location" default="天津市">地区</option>
							</select>
						</div>
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text">日期选择</span>
						</div>
						<input name="date" class="form-control" type="text" value="2019-04-01" need="date" />
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text"">选 择 框</span>
						</div>
						<select class="form-control" adv placeholder="请选择一项">
							<optgroup label="Alaskan/Hawaiian Time Zone">
								<option value="AK">Alaska</option>
								<option value="HI">Hawaii</option>
							</optgroup>
							<optgroup label="Pacific Time Zone">
								<option value="CA">California</option>
								<option value="NV">Nevada</option>
								<option value="OR">Oregon</option>
								<option value="WA">Washington</option>
							</optgroup>
							<optgroup label="Mountain Time Zone">
								<option value="AZ">Arizona</option>
								<option value="CO">Colorado</option>
								<option value="ID">Idaho</option>
								<option value="MT">Montana</option><option value="NE">Nebraska</option>
								<option value="NM">New Mexico</option>
								<option value="ND">North Dakota</option>
								<option value="UT">Utah</option>
								<option value="WY">Wyoming</option>
							</optgroup>
							<optgroup label="Central Time Zone">
								<option value="AL">Alabama</option>
								<option value="AR">Arkansas</option>
								<option value="IL">Illinois</option>
								<option value="IA">Iowa</option>
								<option value="KS">Kansas</option>
								<option value="KY">Kentucky</option>
								<option value="LA">Louisiana</option>
								<option value="MN">Minnesota</option>
								<option value="MS">Mississippi</option>
								<option value="MO">Missouri</option>
								<option value="OK">Oklahoma</option>
								<option value="SD">South Dakota</option>
								<option value="TX">Texas</option>
								<option value="TN">Tennessee</option>
								<option value="WI">Wisconsin</option>
							</optgroup>
							<optgroup label="Eastern Time Zone">
								<option value="CT">Connecticut</option>
								<option value="DE">Delaware</option>
								<option value="FL">Florida</option>
								<option value="GA">Georgia</option>
								<option value="IN">Indiana</option>
								<option value="ME">Maine</option>
								<option value="MD">Maryland</option>
								<option value="MA">Massachusetts</option>
								<option value="MI">Michigan</option>
								<option value="NH">New Hampshire</option><option value="NJ">New Jersey</option>
								<option value="NY">New York</option>
								<option value="NC">North Carolina</option>
								<option value="OH">Ohio</option>
								<option value="PA">Pennsylvania</option><option value="RI">Rhode Island</option><option value="SC">South Carolina</option>
								<option value="VT">Vermont</option><option value="VA">Virginia</option>
								<option value="WV">West Virginia</option>
							</optgroup>
						</select>
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text">多 选 框</span>
						</div>
						<select multiple class="form-control" adv placeholder="可选择多项">
							<optgroup label="Alaskan/Hawaiian Time Zone">
								<option value="AK">Alaska</option>
								<option value="HI">Hawaii</option>
							</optgroup>
							<optgroup label="Pacific Time Zone">
								<option value="CA">California</option>
								<option value="NV">Nevada</option>
								<option value="OR">Oregon</option>
								<option value="WA">Washington</option>
							</optgroup>
							<optgroup label="Mountain Time Zone">
								<option value="AZ">Arizona</option>
								<option value="CO">Colorado</option>
								<option value="ID">Idaho</option>
								<option value="MT">Montana</option><option value="NE">Nebraska</option>
								<option value="NM">New Mexico</option>
								<option value="ND">North Dakota</option>
								<option value="UT">Utah</option>
								<option value="WY">Wyoming</option>
							</optgroup>
							<optgroup label="Central Time Zone">
								<option value="AL">Alabama</option>
								<option value="AR">Arkansas</option>
								<option value="IL">Illinois</option>
								<option value="IA">Iowa</option>
								<option value="KS">Kansas</option>
								<option value="KY">Kentucky</option>
								<option value="LA">Louisiana</option>
								<option value="MN">Minnesota</option>
								<option value="MS">Mississippi</option>
								<option value="MO">Missouri</option>
								<option value="OK">Oklahoma</option>
								<option value="SD">South Dakota</option>
								<option value="TX">Texas</option>
								<option value="TN">Tennessee</option>
								<option value="WI">Wisconsin</option>
							</optgroup>
							<optgroup label="Eastern Time Zone">
								<option value="CT">Connecticut</option>
								<option value="DE">Delaware</option>
								<option value="FL">Florida</option>
								<option value="GA">Georgia</option>
								<option value="IN">Indiana</option>
								<option value="ME">Maine</option>
								<option value="MD">Maryland</option>
								<option value="MA">Massachusetts</option>
								<option value="MI">Michigan</option>
								<option value="NH">New Hampshire</option><option value="NJ">New Jersey</option>
								<option value="NY">New York</option>
								<option value="NC">North Carolina</option>
								<option value="OH">Ohio</option>
								<option value="PA">Pennsylvania</option><option value="RI">Rhode Island</option><option value="SC">South Carolina</option>
								<option value="VT">Vermont</option><option value="VA">Virginia</option>
								<option value="WV">West Virginia</option>
							</optgroup>
						</select>
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text">模态窗口</span>
						</div>
						<div class="form-control pl-0" style="padding-top:3px;">
							<button class="ml-2" onClick="showPop('newLayer','自定义内容窗口','id','newLayer',400)">自定义内容</button>
							<button class="ml-2" onClick="showPop('newPage','自定义网址窗口','url','./',400, 700)">自定义网址</button>
							<button class="ml-2" onClick="showPop('newImg','自定义图片窗口','img','https://ss2.baidu.com/6ONYsjip0QIZ8tyhnq/it/u=2276456453,1005174699&fm=58&bpow=7416&bpoh=4320',400, 300)">自定义图片</button>
							<button class="ml-2" onClick="showPop('newImg','自定义文字窗口','xxx','类似于alert的信息内容',400)">自定义文字</button>
						</div>
					</div>
				</td>
			</tr>
		</table>
	</div>
</div>

<div id="newLayer" class="popshow d-none">
	<div class="py-2">
		<div class="card bg-success text-white h-100">
			<div class="card-body bg-success">
				<div class="rotate">
					<i class="fa fa-user fa-5x"></i>
				</div>
				<h6 class="text-uppercase">Users</h6>
				<h1 class="display-4">134</h1>
			</div>
		</div>
	</div>
	<div class="input-group mb-3">
		<div class="input-group-prepend">
			<label class="input-group-text" for="inputGroupSelect01">Options</label>
		</div>
		<select class="custom-select" id="inputGroupSelect01">
			<option selected>Choose...</option>
			<option value="1">One</option>
			<option value="2">Two</option>
			<option value="3">Three</option>
		</select>
	</div>

	<div class="input-group mb-3">
		<select class="custom-select" id="inputGroupSelect02">
			<option selected>Choose...</option>
			<option value="1">One</option>
			<option value="2">Two</option>
			<option value="3">Three</option>
		</select>
		<div class="input-group-append">
			<label class="input-group-text" for="inputGroupSelect02">Options</label>
		</div>
	</div>

	<div class="input-group mb-3">
		<div class="input-group-prepend">
			<button class="btn btn-outline-secondary" type="button">Button</button>
		</div>
		<select class="custom-select" id="inputGroupSelect03" aria-label="Example select with button addon">
			<option selected>Choose...</option>
			<option value="1">One</option>
			<option value="2">Two</option>
			<option value="3">Three</option>
		</select>
	</div>

	<div class="input-group">
		<select class="custom-select" id="inputGroupSelect04" aria-label="Example select with button addon">
			<option selected>Choose...</option>
			<option value="1">One</option>
			<option value="2">Two</option>
			<option value="3">Three</option>
		</select>
		<div class="input-group-append">
			<button class="btn btn-outline-secondary" type="button">Button</button>
		</div>
	</div>
</div>

<script language="JavaScript">
    jQuery.vendor('select2', {
		add_css:true,
		callback:function(){
            $.getScript('vendor/select2/i18n/zh-CN.js', function(){
                $("select[adv]").each(function(){
                    var opt = {
                        language: "zh-CN",
                        placeholder: $(this).attr('placeholder'),
                        allowClear: true,
                        minimumInputLength: 0,
                    }
                    if($(this).prop('multiple')) {
                        opt.maximumSelectionSize = 3;
                        opt.tags = ["HI","AK"];
                        opt.tokenSeparators = [",", " "];
                    }
                    $(this).select2(opt);
                    $(this).next().removeAttr('style');
                    $(this).next().css({
                        'padding':0,
                        'border-width':0
                    });
                    $(this).next().find('.select2-selection').css({
                        'width':'auto',
                        'height':'100%'
                    });
                    var classes = $(this).attr("class").split(' ');
                    for(var c in classes){
                        if(!isNaN(c) && classes[c].indexOf('select2')==-1) {
                            $(this).next().addClass(classes[c]);
                        }
                    }
                });
            });
		}
    });
	jQuery.vendor('jquery.date_input', {add_css:true});
    jQuery.vendor('jquery.jmpopups', {add_css:true});
    jQuery.vendor('jquery.autocomplete', {
		add_css:true,
		callback:function(){
			ac_options = {
				serviceUrl:'myStep/api/autoComplete/json',
				minChars:1,
				delimiter: /(,|;)\s*/,
				maxHeight:400,
				width:300,
				zIndex: 9999,
				deferRequestBy: 0,
				params: { mode:$('#auto_mode').val()},
				noCache: false,
				onSelect: function(value, data){return;}
			};
			$('#auto_fill').autocomplete(ac_options);
			$('#auto_mode').change(function(){
				$('#auto_fill').val('').attr('placeholder', $(this.options[this.selectedIndex]).attr('default'));
				$('#auto_fill').unbind();
				ac_options.params.mode = this.value;
				$('#auto_fill').autocomplete(ac_options);
			});
		}
    });
</script>
