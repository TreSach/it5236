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
					var sql = "INSERT INTO emailvalidation (emailvalidationid, userid, email, emailsent) " +
		                "VALUES (?, ?, ?, NOW())";
					conn.query(sql, [event.emailvalidationid, event.userid, event.email, event.emailsent], function (err, result) {
						if (err) {
							// Check for duplicate values
						
								// This should be a "Internal Server Error" error
								callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
			      		} else {
			      			console.log("Sending email to " + event.email);
			      			callback(null, "Email sent");
			      			conn.end();
			      		}
		    			}); //query users
		}); //connect database
	} //no validation errors
} //handler
