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
					var sql = "SELECT email, userid FROM users WHERE username = ? OR email = ?";
					conn.query(sql, [event.username, event.email], function(err, result) {
							if (err) {
							// Check for duplicate values
								
								// This should be a "Internal Server Error" error
								callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
								
								if(!event.username || !event.email){
									callback(formatErrorResponse("BAD_REQUEST", "Missing username or email"));
								}
			      		} else {
			      	
			      			console.log("Entry found");
			      			
			    			var sql = "INSERT INTO passwordreset (passwordresetid, userid, email, expires) " +
                        "VALUES (?, '" + result[0].userid + "', '" + result[0].email + "', DATE_ADD(NOW(), INTERVAL 1 HOUR))";
			      			
			      			conn.query(sql, [event.passwordresetid], function(err, result) {
			      					if(err){
			      						
			      						callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
			      					} else {
			      						console.log("Posting new entry");
			      					
			      							
			      						
			      							}
			      					
			      					
			
		    	  			//query users
										});//connect database
										
										
										var json = {
											username : event.username,
											email: result[0].email,
											userid: result[0].userid,
											passwordresetid: event.passwordresetid
										}
										
										callback(null, json);
										conn.end();
							} //no validation errors
					});
			});
	}
};//handler
