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
	//instruct the function to return as soon as the callback is invoked
	context.callbackWaitsForEmptyEventLoop = false;

	//validate input
	var errors = new Array();

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
				var sql = "SELECT userid FROM passwordreset WHERE passwordresetid = ? AND expires > NOW()";
				conn.query(sql, [event.passwordresetid], function (err, result) {
		  	if (err) {
				// This should be a "Internal Server Error" error
				callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
		  	} else if (!event.passwordresetid){
		  			
		  			callback(formatErrorResponse('BAD_REQUEST', "Invalid Request"));
		  		} 
		  			else {
		  			var json = {
		  				passwordresetid : event.passwordresetid,
		  				userid : result[0].userid
		  			}
				
					callback(null, json);
					conn.end();
				} //good code count
		  	}); //query registration codes
		}); //connect database
	} //no validation errors
} //handler
