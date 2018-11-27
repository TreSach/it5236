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
					var sql = "INSERT INTO auditlog (context, message, logdate, ipaddress, userid) " +
            "VALUES (?, ?, NOW(), ?, ?)";
					conn.query(sql, [event.context, event.message,  event.ipaddress, event.userid], function (err, result) {
						if (err) {
							// This should be a "Internal Server Error" error
								callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
								
							// Check if the foriegn keys exist. If it does not exist, foriegn key constraint fails
						 if(!event.ipaddress){
						 	callback(formatErrorResponse('INTERNAL_SERVER_ERROR', ["Server Error - Error Logging failed"]));
						 	
						 }
							
						} else {
							console.log("Inserting new auditlog");
			      			callback(null, "Audit Log Inserted!");
			      			conn.end();
						}
		    			}); //query users
		}); //connect database
	} //no validation errors
} //handler
