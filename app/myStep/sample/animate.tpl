<link rel="stylesheet" media="screen" type="text/css" href="vendor/animate/animate.css" />
<style>
	.mega {
		font-size: 6rem;
		line-height: 1;
		color: #f35626;
		background-image: -webkit-linear-gradient(92deg,#f35626,#feab3a);
		-webkit-background-clip: text;
		-webkit-text-fill-color: transparent;
		-webkit-animation: hue 60s infinite linear;
	}
</style>
<div class="card w-100 mb-5 mb-sm-2">
	<div class="card-body p-4">
		<div class="row">
			<select class="custom-select font-weight-bold js--animations">
				<optgroup label="Attention Seekers">
					<option value="bounce">bounce</option>
					<option value="flash">flash</option>
					<option value="pulse">pulse</option>
					<option value="rubberBand">rubberBand</option>
					<option value="shake">shake</option>
					<option value="swing">swing</option>
					<option value="tada">tada</option>
					<option value="wobble">wobble</option>
					<option value="jello">jello</option>
					<option value="heartBeat">heartBeat</option>
				</optgroup>

				<optgroup label="Bouncing Entrances">
					<option value="bounceIn">bounceIn</option>
					<option value="bounceInDown">bounceInDown</option>
					<option value="bounceInLeft">bounceInLeft</option>
					<option value="bounceInRight">bounceInRight</option>
					<option value="bounceInUp">bounceInUp</option>
				</optgroup>

				<optgroup label="Bouncing Exits">
					<option value="bounceOut">bounceOut</option>
					<option value="bounceOutDown">bounceOutDown</option>
					<option value="bounceOutLeft">bounceOutLeft</option>
					<option value="bounceOutRight">bounceOutRight</option>
					<option value="bounceOutUp">bounceOutUp</option>
				</optgroup>

				<optgroup label="Fading Entrances">
					<option value="fadeIn">fadeIn</option>
					<option value="fadeInDown">fadeInDown</option>
					<option value="fadeInDownBig">fadeInDownBig</option>
					<option value="fadeInLeft">fadeInLeft</option>
					<option value="fadeInLeftBig">fadeInLeftBig</option>
					<option value="fadeInRight">fadeInRight</option>
					<option value="fadeInRightBig">fadeInRightBig</option>
					<option value="fadeInUp">fadeInUp</option>
					<option value="fadeInUpBig">fadeInUpBig</option>
				</optgroup>

				<optgroup label="Fading Exits">
					<option value="fadeOut">fadeOut</option>
					<option value="fadeOutDown">fadeOutDown</option>
					<option value="fadeOutDownBig">fadeOutDownBig</option>
					<option value="fadeOutLeft">fadeOutLeft</option>
					<option value="fadeOutLeftBig">fadeOutLeftBig</option>
					<option value="fadeOutRight">fadeOutRight</option>
					<option value="fadeOutRightBig">fadeOutRightBig</option>
					<option value="fadeOutUp">fadeOutUp</option>
					<option value="fadeOutUpBig">fadeOutUpBig</option>
				</optgroup>

				<optgroup label="Flippers">
					<option value="flip">flip</option>
					<option value="flipInX">flipInX</option>
					<option value="flipInY">flipInY</option>
					<option value="flipOutX">flipOutX</option>
					<option value="flipOutY">flipOutY</option>
				</optgroup>

				<optgroup label="Lightspeed">
					<option value="lightSpeedIn">lightSpeedIn</option>
					<option value="lightSpeedOut">lightSpeedOut</option>
				</optgroup>

				<optgroup label="Rotating Entrances">
					<option value="rotateIn">rotateIn</option>
					<option value="rotateInDownLeft">rotateInDownLeft</option>
					<option value="rotateInDownRight">rotateInDownRight</option>
					<option value="rotateInUpLeft">rotateInUpLeft</option>
					<option value="rotateInUpRight">rotateInUpRight</option>
				</optgroup>

				<optgroup label="Rotating Exits">
					<option value="rotateOut">rotateOut</option>
					<option value="rotateOutDownLeft">rotateOutDownLeft</option>
					<option value="rotateOutDownRight">rotateOutDownRight</option>
					<option value="rotateOutUpLeft">rotateOutUpLeft</option>
					<option value="rotateOutUpRight">rotateOutUpRight</option>
				</optgroup>

				<optgroup label="Sliding Entrances">
					<option value="slideInUp">slideInUp</option>
					<option value="slideInDown">slideInDown</option>
					<option value="slideInLeft">slideInLeft</option>
					<option value="slideInRight">slideInRight</option>

				</optgroup>
				<optgroup label="Sliding Exits">
					<option value="slideOutUp">slideOutUp</option>
					<option value="slideOutDown">slideOutDown</option>
					<option value="slideOutLeft">slideOutLeft</option>
					<option value="slideOutRight">slideOutRight</option>

				</optgroup>

				<optgroup label="Zoom Entrances">
					<option value="zoomIn">zoomIn</option>
					<option value="zoomInDown">zoomInDown</option>
					<option value="zoomInLeft">zoomInLeft</option>
					<option value="zoomInRight">zoomInRight</option>
					<option value="zoomInUp">zoomInUp</option>
				</optgroup>

				<optgroup label="Zoom Exits">
					<option value="zoomOut">zoomOut</option>
					<option value="zoomOutDown">zoomOutDown</option>
					<option value="zoomOutLeft">zoomOutLeft</option>
					<option value="zoomOutRight">zoomOutRight</option>
					<option value="zoomOutUp">zoomOutUp</option>
				</optgroup>

				<optgroup label="Specials">
					<option value="hinge">hinge</option>
					<option value="jackInTheBox">jackInTheBox</option>
					<option value="rollIn">rollIn</option>
					<option value="rollOut">rollOut</option>
				</optgroup>
			</select>
		</div>
		<div class="row">
			<span class="mx-auto" id="animationSandbox"><h1 class="site__title mega">Animate.css</h1></span>
		</div>
	</div>
</div>

<script language="JavaScript">
    $(function(){
        $('.js--animations').change(function(){
            let x = $(this).val();
            $('#animationSandbox').removeClass().addClass('mx-auto').addClass(x + ' animated').one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function(){
                $(this).removeClass().addClass('mx-auto');
            });
        });
    });
</script>