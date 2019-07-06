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
					var sql = "INSERT INTO attachmenttypes (attachmenttypeid, name, extension) VALUES (?, ?, ?)";
					conn.query(sql, [event.attachmenttypeid, event.name,  event.extension], function (err, result) {
						if (err) {
							// This should be a "Internal Server Error" error
								callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
								
						
							
						} else {
							if(!event.attachmenttypeid){
								callback(formatErrorResponse('BAD_REQUEST', "Invalid Request"));
							} else if(!event.name){
								callback(formatErrorResponse('BAD_REQUEST', "This file does not have a name"));
							}else if(!event.extension){
								callback(formatErrorResponse('BAD_REQUEST', "Unknown file extension"));
							} else {
							console.log("Inserting attachment type");
			      			callback(null, "Attachment Type inserted");
			      			conn.end();
						}
						}
		    			}); //query users
		}); //connect database
	} //no validation errors
} //handler
