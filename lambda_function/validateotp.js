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
					var sql = "INSERT INTO otpemail (otp, userid, email) " +
		                "VALUES (?, ?, ?)";
					conn.query(sql, [event.otp, event.userid, event.email], function (err, result) {
						if (err) {
							// Check for duplicate values
						
								// This should be a "Internal Server Error" error
								callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
			      		} else {
			      			console.log("OTP inserted!");
			      			callback(null, "OTP saved");
			      			conn.end();
			      		}
		    			}); //query users
		}); //connect database
	} //no validation errors
} //handler
