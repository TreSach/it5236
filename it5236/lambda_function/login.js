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
	
	 // Validate the user input
	validator.validateUsername(event.username, errors);


	
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
		var sql = "SELECT userid, passwordhash, email, emailvalidated FROM users WHERE username = ?";
		
		conn.query(sql, [event.username], function (err, result) {
		
		  	if (err) {
				// This should be a "Internal Server Error" error
				callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
		  	} else {
		  	if(!event.username){
		  		console.log(event.username);
		  		callback(formatErrorResponse('BAD_REQUEST', ["Missing Username"]));
		  	} 
		  	
		  	else{
		  		console.log("good");
		  		}// Pull out just the codes from the "result" array (index '1')
		  		
				
				// Build an object for the JSON response with the userid and reg codes
				var json = { 
					username : event.username,
					userid : result[0]['userid'],
					passwordhash : result[0]['passwordhash'],
					email : result[0]['email'],
					emailvalidated : result[0]['emailvalidated']
				};
				// Return the json object
      			callback(null, json);
		  	setTimeout( function() {
						conn.end();
					}, 3000);
		  		}//good registration
				}); //good code count
		  	 //query registration codes
		}); //connect database
	}
}	//no validation errors
 //handler