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
					var sql = "SELECT userid FROM emailvalidation WHERE emailvalidationid = ?";
					conn.query(sql, [event.emailvalidationid], function(err, result) {
							if (err) {
							// Check for duplicate values
								
								// This should be a "Internal Server Error" error
								callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
			      		} else {
			      	
			      			console.log("ID found");
			      			
			    			var sql = "UPDATE users SET emailvalidated = 1 WHERE userid = '" + result[0].userid + "'";
			      			
			      			conn.query(sql, function(err, result) {
			      					if(err){
			      						
			      						callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
			      					} else {
			      						console.log("Updating entry");
			      					
			      									var sql = "DELETE FROM emailvalidation WHERE emailvalidationid = ?";
			      				
			      						conn.query(sql, [event.emailvalidationid], function(err, result) {
			      							
			      							
			      							if(err) {
			      								
			      								callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
			      							} else {
			      								
								
			      								console.log("Deleting entry");
			      								callback(null, "Email Validated");
			      							 conn.end();
			      						
			      							}
			      							
			      						});
			      						
			      							}
			      					
			      					
			
		    	  			//query users
										});//connect database
							} //no validation errors
					});
			});
	}
};//handler
