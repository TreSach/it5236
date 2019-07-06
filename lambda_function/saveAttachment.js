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
					var sql = "INSERT INTO attachments (attachmentid, filename) VALUES (?, ?)";
					conn.query(sql, [event.attachmentid, event.filename], function (err, result) {
						if (err) {
							// This should be a "Internal Server Error" error
								callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
							if(!event.filename){
								callback(formatErrorResponse('BAD_REQUEST', "Missing File"));
							}
							
						} else {
							console.log("Saving attachment");
			      			callback(null, "Attachment saved");
			      			conn.end();
						}
		    			}); //query users
		}); //connect database
	} //no validation errors
} //handler
