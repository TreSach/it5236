var mysql = require('./node_modules/mysql');
var config = require('./config.json');
var validator = require('./validation.js');

function formatErrorResponse(code, errs) {
	return JSON.stringify({
		error  : code,
		errors : errs
	});
}

exports.handler = (event, context, callback) => {

	//validate input
	var errors = new Array();

	 // Validate the user input
	validator.validateOTP(event.otp, errors);

	if(errors.length > 0) {
		// This should be a "Bad Request" error
		callback(formatErrorResponse('BAD_REQUEST', errors));
	} else {

	//getConnection equivalent
	var conn = mysql.createConnection({
		host 	: config.dbhost,
		user 	: config.dbuser,
		password : config.dbpassword,
		database : config.dbname
	});

	//prevent timeout from waiting event loop
	context.callbackWaitsForEmptyEventLoop = false;

	//attempts to connect to the database
	conn.connect(function(err) {

		if (err)  {
			// This should be a "Internal Server Error" error
			callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
		};
		console.log("Connected!");
				var sql = "SELECT COUNT(*) AS codecount FROM otpemail WHERE otp = ?";
				conn.query(sql, [event.otp], function (err, result) {
		  	if (err) {
				// This should be a "Internal Server Error" error
				callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
		  	} else {
		    	console.log("OTP code count is " + result[0].codecount);
		    	if (result[0].codecount == 0){
		    		errors.push("Invalid OTP");
					callback(formatErrorResponse('BAD_REQUEST', errors));
		    	} else {
					console.log("OTP Validated!");
					callback(null, "login successfully");
					conn.end();
		  			} //good registration
				} //good code count
		  	}); //query registration codes
		}); //connect database
	} //no validation errors
} //handler
