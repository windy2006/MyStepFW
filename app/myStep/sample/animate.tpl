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
                    <option>bounce</option>
                    <option>flash</option>
                    <option>pulse</option>
                    <option>rubberBand</option>
                    <option>shakeX</option>
                    <option>shakeY</option>
                    <option>headShake</option>
                    <option>swing</option>
                    <option>tada</option>
                    <option>wobble</option>
                    <option>jello</option>
                    <option>heartBeat</option>
                </optgroup>

                <optgroup label="Back entrances">
                    <option>backInDown</option>
                    <option>backInLeft</option>
                    <option>backInRight</option>
                    <option>backInUp</option>
                </optgroup>

                <optgroup label="Back exits">
                    <option>backOutDown</option>
                    <option>backOutLeft</option>
                    <option>backOutRight</option>
                    <option>backOutUp</option>
                </optgroup>

                <optgroup label="Bouncing entrances">
                    <option>bounceIn</option>
                    <option>bounceInDown</option>
                    <option>bounceInLeft</option>
                    <option>bounceInRight</option>
                    <option>bounceInUp</option>
                </optgroup>

                <optgroup label="Bouncing exits">
                    <option>bounceOut</option>
                    <option>bounceOutDown</option>
                    <option>bounceOutLeft</option>
                    <option>bounceOutRight</option>
                    <option>bounceOutUp</option>
                </optgroup>

                <optgroup label="Fading entrances">
                    <option>fadeIn</option>
                    <option>fadeInDown</option>
                    <option>fadeInDownBig</option>
                    <option>fadeInLeft</option>
                    <option>fadeInLeftBig</option>
                    <option>fadeInRight</option>
                    <option>fadeInRightBig</option>
                    <option>fadeInUp</option>
                    <option>fadeInUpBig</option>
                    <option>fadeInTopLeft</option>
                    <option>fadeInTopRight</option>
                    <option>fadeInBottomLeft</option>
                    <option>fadeInBottomRight</option>
                </optgroup>

                <optgroup label="Fading exits">
                    <option>fadeOut</option>
                    <option>fadeOutDown</option>
                    <option>fadeOutDownBig</option>
                    <option>fadeOutLeft</option>
                    <option>fadeOutLeftBig</option>
                    <option>fadeOutRight</option>
                    <option>fadeOutRightBig</option>
                    <option>fadeOutUp</option>
                    <option>fadeOutUpBig</option>
                    <option>fadeOutTopLeft</option>
                    <option>fadeOutTopRight</option>
                    <option>fadeOutBottomRight</option>
                    <option>fadeOutBottomLeft</option>
                </optgroup>

                <optgroup label="Flippers">
                    <option>flip</option>
                    <option>flipInX</option>
                    <option>flipInY</option>
                    <option>flipOutX</option>
                    <option>flipOutY</option>
                </optgroup>

                <optgroup label="Lightspeed">
                    <option>lightSpeedInRight</option>
                    <option>lightSpeedInLeft</option>
                    <option>lightSpeedOutRight</option>
                    <option>lightSpeedOutLeft</option>
                </optgroup>

                <optgroup label="Rotating entrances">
                    <option>rotateIn</option>
                    <option>rotateInDownLeft</option>
                    <option>rotateInDownRight</option>
                    <option>rotateInUpLeft</option>
                    <option>rotateInUpRight</option>
                </optgroup>

                <optgroup label="Rotating exits">
                    <option>rotateOut</option>
                    <option>rotateOutDownLeft</option>
                    <option>rotateOutDownRight</option>
                    <option>rotateOutUpLeft</option>
                    <option>rotateOutUpRight</option>
                </optgroup>

                <optgroup label="Specials">
                    <option>hinge</option>
                    <option>jackInTheBox</option>
                    <option>rollIn</option>
                    <option>rollOut</option>
                </optgroup>

                <optgroup label="Zooming entrances">
                    <option>zoomIn</option>
                    <option>zoomInDown</option>
                    <option>zoomInLeft</option>
                    <option>zoomInRight</option>
                    <option>zoomInUp</option>
                </optgroup>

                <optgroup label="Zooming exits">
                    <option>zoomOut</option>
                    <option>zoomOutDown</option>
                    <option>zoomOutLeft</option>
                    <option>zoomOutRight</option>
                    <option>zoomOutUp</option>
                </optgroup>

                <optgroup label="Sliding entrances">
                    <option>slideInDown</option>
                    <option>slideInLeft</option>
                    <option>slideInRight</option>
                    <option>slideInUp</option>
                </optgroup>

                <optgroup label="Sliding exits">
                    <option>slideOutDown</option>
                    <option>slideOutLeft</option>
                    <option>slideOutRight</option>
                    <option>slideOutUp</option>
                </optgroup>
            </select>
        </div>
        <div class="row">
            <span class="mx-auto" id="animationSandbox"><h1 class="site__title mega">Animate.css</h1></span>
        </div>
    </div>
</div>

<script type="application/javascript">
    $(function(){
        $('.js--animations').change(function(){
            let x = $(this).val();
            $('#animationSandbox').removeClass().addClass('mx-auto').addClass('animate__' + x + ' animate__animated').one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd onanimationend animationend', function(){
                $(this).removeClass().addClass('mx-auto');
            });
        });
    });
</script>