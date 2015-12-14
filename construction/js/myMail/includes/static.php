<?php 

$mymail_homepage = array(
		'post_title' => __('Newsletter', 'mymail'),
		'post_status' => 'publish',
		'post_type' => 'page',
		'post_content' => "[newsletter_signup]".__('Signup for the newsletter', 'mymail')."[newsletter_signup_form][/newsletter_signup] [newsletter_confirm]".__('Thanks for your interest!', 'mymail')."[/newsletter_confirm] [newsletter_unsubscribe]".__('Do you really want to unsubscribe?', 'mymail')."[/newsletter_unsubscribe]",
);

$mymail_form_css = "
/* normal input fields */
.mymail-form {
	margin-bottom:20px;
}
.mymail-form .input, .mymail-form .mymail-form-info {
	width:100%;
	-webkit-box-sizing:border-box;
	-moz-box-sizing:border-box;
	box-sizing:border-box;  
}
.mymail-form label {
	line-height:1.6em;
}

.mymail-form li{
	list-style:none !important;
	margin-left:0;
}
/* the asterix (*) for required fields */
.mymail-form label .required {
	color:#f33;
}
.mymail-form input.required {
	color:inherit;
}

/* target individual input fields */
.mymail-form .mymail-email {
}

.mymail-form .mymail-firstname {
}

.mymail-form .mymail-lastname {
}

/* lists */
.mymail-form .mymail-lists-wrapper ul {
	list-style:none;
	margin-left:0;
}
.mymail-form .mymail-lists-wrapper ul li{
	margin-left:0;
}

.mymail-form .mymail-list-description {
	color:inherit;
	display:block;
	margin-left:25px;
	font-size:0.8em;
}

.mymail-form .mymail-form-info {
	display:none;
	border-radius:3px;
	padding:5px;
	margin-bottom:4px;
}

/* inputs with errors */
.mymail-form .error input {
	border:1px solid;
	border-color:#df6f8b #da5f75 #d55061;
}

/* info box with error */
.mymail-form .mymail-form-info.error {
	border:1px solid #E9B9BB;
	background:#FFDCDD;
}

.mymail-form .mymail-form-info ul li{
	color:inherit;
	margin-left:0;
}

/*info box with success */
.mymail-form .mymail-form-info.success {
	border:1px solid #BADEB1;
	background-color: #E1FFD9;
}

.mymail-form .mymail-form-info p {
	margin-bottom:0;
}
.mymail-form .mymail-form-info ul {
	list-style-type:circle;
	margin-left:0;
	margin-bottom:0;
}

/* submit button */
.mymail-form .submit-button {
	margin:6px 0 0;
}
.mymail-form .submit-button:active {
	margin:7px 1px 1px;
}
.mymail-form .mymail-loader {
	display: none;
	width:16px;
	height:16px;
	margin:4px;
	vertical-align: middle;
	background-image:url('MYMAIL_URI/assets/img/loading.gif');
	background-repeat:no-repeat;
	background-position:center center;
}
.mymail-form .mymail-loader.loading {
	display: inline-block;
}
@media 
only screen and (-webkit-min-device-pixel-ratio: 2),
only screen and (   min--moz-device-pixel-ratio: 2),
only screen and (     -o-min-device-pixel-ratio: 2/1),
only screen and (        min-device-pixel-ratio: 2),
only screen and (                min-resolution: 192dpi),
only screen and (                min-resolution: 2dppx) { 
	.mymail-form .mymail-loader {
		background-image:url('MYMAIL_URI/assets/img/loading_2x.gif');
		background-size:100%;
	}
}
";

?>