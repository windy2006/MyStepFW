<?PHP
//initialize the instance
myException::init(array(
	'log_mode' => 0,
	'log_type' => E_ALL,
	'log_file' => ROOT.'error.log',
	'callback_type' => E_ALL,
	'exit_on_error' => false
));

//Division by zero
$num = 0;
echo 1/$num;

//Undefined variable
echo $noset_para;

//User triggered error
trigger_error('User Error', E_USER_WARNING);

//Throwed error
try {
	throw new myException('Throwed error', 0, E_USER_ERROR, __FILE__, __LINE__);
} catch(myException $e) {}

//undefined function
NoSet();