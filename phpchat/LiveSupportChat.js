class LiveSupportChat {

    // Executes when creating a new instance of the class
    constructor(options) {
        // Default options
        let defaults = {
            auto_login: true,
            php_directory_url: '',
            status: 'Idle',
            departments: '',
            update_interval: 5000,
            current_chat_widget_tab: 1,
            conversation_id: null,
            notifications: true,
            background_color: '',
            files: {
                'authenticate': 'authenticate.php',
                'conversation': 'conversation.php',
                'conversations': 'conversations.php',
                'find_conversation': 'find_conversation.php',
                'post_message': 'post_message.php',
                'logout': 'logout.php',
                'get_details': 'get_details.php',
                'post_email_message': 'post_email_message.php'
            },
            icon: `
            <svg width="30" height="30" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2.75C6.89137 2.75 2.75 6.89137 2.75 12C2.75 13.4811 3.09753 14.8788 3.7148 16.1181C3.96254 16.6155 4.05794 17.2103 3.90163 17.7945L3.30602 20.0205C3.19663 20.4293 3.57066 20.8034 3.97949 20.694L6.20553 20.0984C6.78973 19.9421 7.38451 20.0375 7.88191 20.2852C9.12121 20.9025 10.5189 21.25 12 21.25C17.1086 21.25 21.25 17.1086 21.25 12C21.25 6.89137 17.1086 2.75 12 2.75ZM1.25 12C1.25 6.06294 6.06294 1.25 12 1.25C17.9371 1.25 22.75 6.06294 22.75 12C22.75 17.9371 17.9371 22.75 12 22.75C10.2817 22.75 8.65552 22.3463 7.21315 21.6279C6.99791 21.5207 6.77814 21.4979 6.59324 21.5474L4.3672 22.143C2.84337 22.5507 1.44927 21.1566 1.857 19.6328L2.4526 17.4068C2.50208 17.2219 2.47933 17.0021 2.37213 16.7869C1.65371 15.3445 1.25 13.7183 1.25 12ZM7.25 10.5C7.25 10.0858 7.58579 9.75 8 9.75H16C16.4142 9.75 16.75 10.0858 16.75 10.5C16.75 10.9142 16.4142 11.25 16 11.25H8C7.58579 11.25 7.25 10.9142 7.25 10.5ZM7.25 14C7.25 13.5858 7.58579 13.25 8 13.25H13.5C13.9142 13.25 14.25 13.5858 14.25 14C14.25 14.4142 13.9142 14.75 13.5 14.75H8C7.58579 14.75 7.25 14.4142 7.25 14Z"/>
            </svg>
            `
        };
        // Assign new options
        this.options = Object.assign(defaults, options);
        // Chat icon template
        document.body.insertAdjacentHTML('afterbegin', `<a href="#" class="open-chat-widget"${this.options.background_color != '' ? ' style="background-color:' + this.options.background_color + '"' : ''}>${this.icon}</a>`);
        // Chat widget template
        document.body.insertAdjacentHTML('afterbegin', `
        <div class="chat-widget">
            <div class="chat-widget-header">
                <a href="#" class="previous-chat-tab-btn">&lsaquo;</a>
                <a href="#" class="close-chat-widget-btn">&times;</a>
            </div>
            <div class="chat-widget-content">
                <div class="chat-widget-tabs">
                    <div class="chat-widget-tab chat-widget-login-tab">
                        <svg width="100" height="100" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path opacity="0.4" d="M12 22.01C17.5228 22.01 22 17.5329 22 12.01C22 6.48716 17.5228 2.01001 12 2.01001C6.47715 2.01001 2 6.48716 2 12.01C2 17.5329 6.47715 22.01 12 22.01Z" fill="#d2d5d9"/>
                            <path d="M12 6.93994C9.93 6.93994 8.25 8.61994 8.25 10.6899C8.25 12.7199 9.84 14.3699 11.95 14.4299C11.98 14.4299 12.02 14.4299 12.04 14.4299C12.06 14.4299 12.09 14.4299 12.11 14.4299C12.12 14.4299 12.13 14.4299 12.13 14.4299C14.15 14.3599 15.74 12.7199 15.75 10.6899C15.75 8.61994 14.07 6.93994 12 6.93994Z" fill="#d2d5d9"/>
                            <path d="M18.7807 19.36C17.0007 21 14.6207 22.01 12.0007 22.01C9.3807 22.01 7.0007 21 5.2207 19.36C5.4607 18.45 6.1107 17.62 7.0607 16.98C9.7907 15.16 14.2307 15.16 16.9407 16.98C17.9007 17.62 18.5407 18.45 18.7807 19.36Z" fill="#d2d5d9"/>
                        </svg>
                        <form action="" method="post" autocomplete="off">
                            <input type="text" name="name" placeholder="Your Name">
                            <input type="email" name="email" placeholder="Your Email" required>
                            <div class="msg"></div>
                            <button type="submit">Submit</button>
                        </form>
                    </div>
                    <div class="chat-widget-tab chat-widget-conversations-tab"></div>
                    <div class="chat-widget-tab chat-widget-conversation-tab"></div>
                    <div class="chat-widget-tab chat-widget-offline-tab">
                        <p>There are no operators online at the moment. Please leave a message below and we'll get back to you ASAP!</p>
                        <form action="" method="post" autocomplete="off">
                            <input type="text" name="name" placeholder="Your Name" required>
                            <input type="email" name="email" placeholder="Your Email" required>
                            <textarea name="message" placeholder="Your Message" required></textarea>
                            <div class="msg"></div>
                            <button type="submit">Submit</button>
                            <button class="back-btn alt" type="button">Back to Messages</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        `);
        // Declare class variables for easy access
        this.openWidgetBtn = document.querySelector('.open-chat-widget');
        this.container = document.querySelector('.chat-widget');
        // Authenticate user if cookie secret exists
        if (this.autoLogin && document.cookie.match(/^(.*;)?\s*chat_secret\s*=\s*[^;]+(.*)?$/)) {
            // Execute GET AJAX request to retireve the conversations
            this.fetchConversations(data => {
                // If respone not equals error
                if (data != 'error') {
                    // User is authenticated! Update the status and conversations tab content
                    this.status = 'Idle';
                    this.container.querySelector('.chat-widget-conversations-tab').innerHTML = data;
                    // Execute the conversation handler function
                    this._eventHandlers();
                    // Transition to the conversations tab
                    this.selectChatWidgetTab(2);
                }
            });
        }
        // Execute event handlers
        this._eventHandlers();
        // Fetch relevant details
        this.fetchDetails();
        // Update chat every X
        setInterval(() => this.update(), this.options.update_interval);
    }

    // AJAX method that will authenticate user based on an HTML Form element
    authenticateUser(form, callback = () => {}) {   
        // Execute POST AJAX request and attempt to authenticate the user
        fetch(this.phpDirectoryUrl + this.files['authenticate'], {
            cache: 'no-store',
            method: 'POST',
            body: new FormData(form)
        }).then(response => response.json()).then(data => callback(data));      
    }

    // AJAX method that will Logout user
    logOutUser(callback = () => {}) {
        document.cookie = 'chat_secret=;expires=Thu, 01 Jan 1970 00:00:01 GMT;';
        fetch(this.phpDirectoryUrl + this.files['logout'], { cache: 'no-store' }).then(response => response.text()).then(data => callback(data));
    }

    // AJAX method that will fetch the conversations list associated with the user
    fetchConversations(callback = () => {}) {
        fetch(this.phpDirectoryUrl + this.files['conversations'], { cache: 'no-store' }).then(response => response.text()).then(data => callback(data));       
    }

    // AJAX method that will fetch the conversation associated with the user and ID param
    fetchConversation(id, callback = () => {}) {
        fetch(this.phpDirectoryUrl + this.files['conversation'] + `${this.files['conversation'].includes('?')?'&':'?'}id=` + id, { cache: 'no-store' }).then(response => response.text()).then(data => callback(data));
    }

    // AJAX method that will fetch relevant details
    fetchDetails() {
        fetch(this.phpDirectoryUrl + this.files['get_details'], { cache: 'no-store' }).then(response => response.json()).then(data => {
            // Update the details variable
            this.details = data;
            // If no ops are online and mail is enabled, update the chat widget button class
            if (this.details.ops_online === 0 && this.details.mail_enabled) {
                this.openWidgetBtn.classList.add('offline');
                // If the user is logged in, show the back button on the offline tab
                if (this.details.is_loggedin) {
                    this.container.querySelector('.chat-widget-offline-tab .back-btn').style.display = 'block';
                }
            } else {
                this.openWidgetBtn.classList.remove('offline');
            }
            // If notifications are enabled
            if (this.notifications) {
                // Determine the current number of messages
                let numMessages = document.querySelector('.open-chat-widget').dataset.messages ? parseInt(document.querySelector('.open-chat-widget').dataset.messages) : 0;
                // If total number is greater than zero, update the open chat widget button data attribute
                if (parseInt(this.details.messages_total) > 0) {
                    if (parseInt(this.details.messages_total) > numMessages) {
                        let audio = new Audio('notification.ogg');
                        audio.volume = 0.5;
                        audio.play();
                    }
                    document.querySelector('.open-chat-widget').dataset.messages = this.details.messages_total;
                } else if (document.querySelector('.open-chat-widget').dataset.messages) {
                    // If there are no new messages, delete the data attribute
                    delete document.querySelector('.open-chat-widget').dataset.messages;
                }
            }
        });
    }

    // Retrieve a conversation method
    getConversation(id, update = false, scrollPosition = null) {
        // Execute GET AJAX request
        this.fetchConversation(id, data => {
            // Update conversation ID variable
            this.conversationId = id;
            // Update the status
            this.status = 'Occupied';
            // Update the converstaion tab content
            if (!update) {
                this.container.querySelector('.chat-widget-conversation-tab').innerHTML = data;
            } else {
                let doc = (new DOMParser()).parseFromString(data, 'text/html');
                this.container.querySelector('.chat-widget-messages').innerHTML = doc.querySelector('.chat-widget-messages').innerHTML;
                this.container.querySelector('.chat-widget-message-header').innerHTML = doc.querySelector('.chat-widget-message-header').innerHTML;
            }
            // Transition to the conversation tab (tab 3)
            this.selectChatWidgetTab(3);  
            // Retrieve the input message form element 
            let chatWidgetInputMsg = this.container.querySelector('.chat-widget-input-message');
            // If the element exists
            if (chatWidgetInputMsg) {
                // Handle the content scroll position
                if (this.container.querySelector('.chat-widget-messages').lastElementChild) {
                    if (scrollPosition == null) {
                        // Scroll to the bottom of the messages container
                        this.container.querySelector('.chat-widget-messages').scrollTop = this.container.querySelector('.chat-widget-messages').lastElementChild.offsetTop;
                    } else {
                        // Scroll to the preserved position
                        this.container.querySelector('.chat-widget-messages').scrollTop = scrollPosition;
                    }
                }
                // Message submit event handler
                chatWidgetInputMsg.onsubmit = event => {
                    event.preventDefault();
                    // Retrieve the message input element
                    let chatMsgValue = chatWidgetInputMsg.querySelector('input[type="text"]').value;
                    if (chatMsgValue) {
                        // Decode emojis
                        chatWidgetInputMsg.querySelector('input[type="text"]').value = chatWidgetInputMsg.querySelector('input[type="text"]').value.replace(/([\u2700-\u27BF]|[\uE000-\uF8FF]|\uD83C[\uDC00-\uDFFF]|\uD83D[\uDC00-\uDFFF]|[\u2011-\u26FF]|\uD83E[\uDD10-\uDDFF])/g, match => '&#x' + match.codePointAt(0).toString(16).toUpperCase() + ';');
                        // Execute POST AJAX request that will send the captured message to the server and insert it into the database
                        fetch(this.phpDirectoryUrl + this.files['post_message'], { 
                            cache: 'no-store',
                            method: 'POST',
                            body: new FormData(chatWidgetInputMsg)
                        }).then(response => response.json()).then(data => {
                            if (data.msg) {
                                console.log(data.msg);
                            }
                        });
                        // Create the new message element
                        let chatWidgetMsg = document.createElement('div');
                        chatWidgetMsg.classList.add('chat-widget-message');
                        chatWidgetMsg.textContent = chatMsgValue;
                        chatWidgetMsg.innerHTML = chatWidgetMsg.innerHTML.replace(/\n\r?/g, '<br>');
                        // Add it to the messages container, right at the bottom
                        this.container.querySelector('.chat-widget-messages').insertAdjacentElement('beforeend', chatWidgetMsg);
                        // Reset the input form
                        chatWidgetInputMsg.querySelector('input[type="text"]').value = '';
                        chatWidgetInputMsg.querySelector('.files').value = '';
                        this.container.querySelector('.chat-widget-attachments').innerHTML = '';
                        // Scroll to the bottom of the messages container
                        this.container.querySelector('.chat-widget-messages').scrollTop = chatWidgetMsg.offsetTop;
                    }
                    // Focus the input message element
                    chatWidgetInputMsg.querySelector('input[type="text"]').focus();
                };
                // on change event handlers for attachments
                chatWidgetInputMsg.querySelector('.files').onchange = event => {
                    // Reset attachment label
                    document.querySelector('.chat-widget-attachments').innerHTML = '';
                    // Create attachment label
                    let attachmentLink = document.createElement('div');
                    attachmentLink.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" height="12" width="10" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2023 Fonticons, Inc.--><path d="M364.2 83.8c-24.4-24.4-64-24.4-88.4 0l-184 184c-42.1 42.1-42.1 110.3 0 152.4s110.3 42.1 152.4 0l152-152c10.9-10.9 28.7-10.9 39.6 0s10.9 28.7 0 39.6l-152 152c-64 64-167.6 64-231.6 0s-64-167.6 0-231.6l184-184c46.3-46.3 121.3-46.3 167.6 0s46.3 121.3 0 167.6l-176 176c-28.6 28.6-75 28.6-103.6 0s-28.6-75 0-103.6l144-144c10.9-10.9 28.7-10.9 39.6 0s10.9 28.7 0 39.6l-144 144c-6.7 6.7-6.7 17.7 0 24.4s17.7 6.7 24.4 0l176-176c24.4-24.4 24.4-64 0-88.4z"/></svg> ' + event.target.files.length + ' Attachment' + (event.target.files.length > 1 ? 's' : '');
                    document.querySelector('.chat-widget-attachments').appendChild(attachmentLink);
                    let removeAttachmentsLink = document.createElement('a');
                    removeAttachmentsLink.innerHTML = '&times;';
                    document.querySelector('.chat-widget-attachments').appendChild(removeAttachmentsLink);
                    removeAttachmentsLink.onclick = event => {
                        event.preventDefault();
                        document.querySelector('.chat-widget-attachments').innerHTML = '';
                        chatWidgetInputMsg.querySelector('.files').value = '';
                    };
                };
                // Iterate all attachments in chat and add the event handler that will download them once clicked
                this.container.querySelectorAll('.chat-widget-message-attachments').forEach(element => element.onclick = () => {
                    element.nextElementSibling.querySelectorAll('a').forEach(element => element.click());
                });
                // Open attachment file dialog event handler
                if (chatWidgetInputMsg.querySelector('.actions .attach-files')) {
                    chatWidgetInputMsg.querySelector('.actions .attach-files').onclick = event => {
                        event.preventDefault();
                        chatWidgetInputMsg.querySelector('.files').click();
                    };
                }
                // Event handler that will open the emojis box when clicked
                chatWidgetInputMsg.querySelector('.actions .view-emojis svg').onclick = event => {
                    event.preventDefault();
                    chatWidgetInputMsg.querySelector('.actions .emoji-list').classList.toggle('open');
                };
                // Iterate all emojis and add event handler that will add the particular emoji to the input message when clicked
                chatWidgetInputMsg.querySelectorAll('.actions .emoji-list span').forEach(element => element.onclick = () => {
                    chatWidgetInputMsg.querySelector('input[type="text"]').value += element.innerText;
                    chatWidgetInputMsg.querySelector('.actions .emoji-list').classList.remove('open');
                    chatWidgetInputMsg.querySelector('input[type="text"]').focus();
                });
            }
        });
    }

    // Update method that will update various aspects of the chat widget every X miliseconds
    update() {
        // If the current tab is 2
        if (this.currentChatWidgetTab == 2) {
            // Use AJAX to update the conversations list
            this.fetchConversations(data => {
                let doc = (new DOMParser()).parseFromString(data, 'text/html');
                this.container.querySelector('.chat-widget-conversations').innerHTML = doc.querySelector('.chat-widget-conversations').innerHTML;
                this._eventHandlers();
            }); 
        // If the current tab is 3 and the conversation ID variable is not NUll               
        } else if (this.currentChatWidgetTab == 3 && this.conversationId != null) {
            // Use AJAX to update the conversation  
            let scrollPosition = null;
            if (document.querySelector('.chat-widget-messages').lastElementChild && document.querySelector('.chat-widget-messages').scrollHeight - document.querySelector('.chat-widget-messages').scrollTop != document.querySelector('.chat-widget-messages').clientHeight) {
                scrollPosition = this.container.querySelector('.chat-widget-messages').scrollTop;
            } 
            this.getConversation(this.conversationId, true, scrollPosition);
        // If the current tab is 3 and the status is Waiting           
        } else if (this.currentChatWidgetTab == 3 && this.status == 'Waiting') {
            // If the counter is undefined, set it to 0 (used for the automated responses)
            if (this._findConversationCounter == undefined) {
                this._findConversationCounter = 0;
            }
            // Attempt to find a new conversation between the user and operator (or vice-versa)
            fetch(this.phpDirectoryUrl + this.files['find_conversation'] + `${this.files['find_conversation'].includes('?')?'&':'?'}count=${this._findConversationCounter}&departments=${this.departments}`, { cache: 'no-store' }).then(response => response.json()).then(data => {
                // If data includes automated message...
                if (data.status === 'waiting') {
                    // If the message does not exist, add it to the messages container
                    if (!document.querySelector('.chat-widget-message[data-id="' + this._findConversationCounter + '"]') && data.msg) {
                        this.container.querySelector('.chat-widget-messages').innerHTML += `<div class="chat-widget-message" data-id="${this._findConversationCounter}">${data.msg}</div>`;
                    }
                    // Increment the counter
                    this._findConversationCounter++;
                } else if (data.status === 'success') {
                    // Success! Two users are now connected! Retrieve the new conversation
                    this.getConversation(data.msg);
                    // Reset counter
                    this._findConversationCounter = 0;
                }
            });               
        }
        this.fetchDetails();
    }

    // Open chat widget method
    openChatWidget() {
        // If the ops are offline, transition to the offline tab
        if (this.openWidgetBtn.classList.contains('offline')) {
            this.selectChatWidgetTab(4);
        }
        this.container.style.display = 'flex';
        // Animate the chat widget
        this.container.getBoundingClientRect();
        this.container.classList.add('open');
    }

    // Close chat widget method
    closeChatWidget() {
        this.container.classList.remove('open');
        this.status = 'Idle';
    }

    // Select chat tab - it will be used to smoothly transition between tabs
    selectChatWidgetTab(value) {
        // Update the current tab variable
        this.currentChatWidgetTab = value;
        // Select all tab elements and add the CSS3 property transform
        this.container.querySelectorAll('.chat-widget-tab').forEach(element => element.style.transform = `translateX(-${(value-1)*100}%)`);
        // If the user is on the first tab, hide the prev tab button element
        this.container.querySelector('.previous-chat-tab-btn').style.display = value > 1 ? 'block' : 'none';
        // Update the conversation ID variable if the user is on the first or second tab
        if (value == 1 || value == 2 || value == 4) {
            this.conversationId = null;
        }
        // If the user is on the login form tab (tab 1), remove the secret code cookie (logout)
        if (value == 1) {
            this.logOutUser();
        }
        // Hide the back button if the user is on the offline tab
        this.container.querySelector('.previous-chat-tab-btn').style.display = value == 4 ? 'none' : 'block';
    }

    // Event handler method - Add events to all the chat widget interactive elements
    _eventHandlers() {
        // Open chat widget event
        this.openWidgetBtn.onclick = event => {
            event.preventDefault();
            this.openChatWidget();
        };
        // Close button OnClick event handler
        if (this.container.querySelector('.close-chat-widget-btn')) {
            this.container.querySelector('.close-chat-widget-btn').onclick = event => {
                event.preventDefault();
                // Close the chat
                this.closeChatWidget();
            };
        }
        // Previous tab button OnClick event handler
        if (this.container.querySelector('.previous-chat-tab-btn')) {
            this.container.querySelector('.previous-chat-tab-btn').onclick = event => {
                event.preventDefault();
                // Transition to the respective page
                this.selectChatWidgetTab(this.currentChatWidgetTab-1);
            };
        }
        // New chat button OnClick event handler
        if (this.container.querySelector('.chat-widget-new-conversation')) {
            this.container.querySelector('.chat-widget-new-conversation').onclick = event => {
                event.preventDefault();
                // Update the status
                this.status = 'Waiting';
                // Notify the user
                this.container.querySelector('.chat-widget-conversation-tab').innerHTML = `<div class="chat-widget-messages"></div>`;
                // Transition to the conversation tab (tab 3)
                this.selectChatWidgetTab(3);    
                // Execute the update function
                this.update();            
            };
        }
        // Iterate the conversations and add the OnClick event handler to each element
        if (this.container.querySelectorAll('.chat-widget-user')) {
            this.container.querySelectorAll('.chat-widget-user').forEach(element => {
                element.onclick = event => {
                    event.preventDefault();
                    // Get the conversation
                    this.getConversation(element.dataset.id);
                };
            });
        }
        // Ensure the login form exists
        if (this.container.querySelector('.chat-widget-login-tab form')) {
            // Login form submit event
            this.container.querySelector('.chat-widget-login-tab form').onsubmit = event => {
                event.preventDefault();
                // Authenticate the user
                this.authenticateUser(this.container.querySelector('.chat-widget-login-tab form'), data => {
                    // If the response includes the "operator" string
                    if (data.status == 'password_field_required') {
                        // Password field does not exist, add it
                        if (!this.container.querySelector('.chat-widget-login-tab input[name="password"]')) {
                            this.container.querySelector('.chat-widget-login-tab .msg').insertAdjacentHTML('beforebegin', '<input type="password" name="password" placeholder="Your Password" autocomplete="new-password" required>');
                        }
                        // Focus the password field
                        this.container.querySelector('.chat-widget-login-tab input[name="password"]').focus();
                        // Output the error message
                        this.container.querySelector('.chat-widget-login-tab .msg').innerHTML = data.msg;
                    } else if (data.status == 'create_success') {
                        // New user
                        // Authentication success! Execute AJAX request to retrieve the user's conversations
                        this.fetchConversations(data => {
                            // Update the status
                            this.status = 'Waiting';
                            // Notify the user
                            this.container.querySelector('.chat-widget-conversation-tab').innerHTML = `<div class="chat-widget-messages"></div>`;
                            // Update the conversations tab content
                            this.container.querySelector('.chat-widget-conversations-tab').innerHTML = data;
                            // Execute the conversation handler function
                            this._eventHandlers();
                            // Transition to the conversation tab (tab 3)
                            this.selectChatWidgetTab(3); 
                            // Execute the update function 
                            this.update();
                        });
                    } else if (data.status == 'success') {
                        // Authentication success! Execute AJAX request to retrieve the user's conversations
                        this.fetchConversations(data => {
                            // Update the status
                            this.status = 'Idle';
                            // Update the conversations tab content
                            this.container.querySelector('.chat-widget-conversations-tab').innerHTML = data;
                            // Execute the conversation handler function
                            this._eventHandlers();
                            // Transition to the conversations tab
                            this.selectChatWidgetTab(2);
                        });
                    } else {
                        // Authentication failed! Show the error message on the form
                        this.container.querySelector('.chat-widget-login-tab .msg').innerHTML = data.msg;
                    }
                });
            };
        }
        // Ensure the offline form exists
        if (this.container.querySelector('.chat-widget-offline-tab form')) {
            // Offline form submit event
            this.container.querySelector('.chat-widget-offline-tab form').onsubmit = event => {
                event.preventDefault();
                // Execute POST AJAX request and attempt to authenticate the user
                fetch(this.phpDirectoryUrl + this.files['post_email_message'], { 
                    cache: 'no-store',
                    method: 'POST',
                    body: new FormData(this.container.querySelector('.chat-widget-offline-tab form'))
                }).then(response => response.json()).then(data => {
                    this.container.querySelector('.chat-widget-offline-tab .msg').innerHTML = data.msg;
                });
            };
            // Back button OnClick event handler
            this.container.querySelector('.chat-widget-offline-tab .back-btn').onclick = event => {
                event.preventDefault();
                // Transition to the conversations tab
                this.selectChatWidgetTab(2);
            };
        }
    }

    /* Below are class methods for easy access to the options that are declared in the constructor */

    get phpDirectoryUrl() {
        return this.options.php_directory_url;
    }

    set phpDirectoryUrl(value) {
        this.options.php_directory_url = value;
    }

    get currentChatWidgetTab() {
        return this.options.current_chat_widget_tab;
    }

    set currentChatWidgetTab(value) {
        this.options.current_chat_widget_tab = value;
    }

    get conversationId() {
        return this.options.conversation_id;
    }

    set conversationId(value) {
        this.options.conversation_id = value;
    }

    get files() {
        return this.options.files;
    }

    set files(value) {
        this.options.files = value;
    }

    get container() {
        return this.options.container;
    }

    set container(value) {
        this.options.container = value;
    }

    get status() {
        return this.options.status;
    }

    set status(value) {
        this.options.status = value;
    }

    get notifications() {
        return this.options.notifications;
    }

    set notifications(value) {
        this.options.notifications = value;
    }

    get autoLogin() {
        return this.options.auto_login;
    }

    set autoLogin(value) {
        this.options.auto_login = value;
    }

    get details() {
        return this.options.details;
    }

    set details(value) {
        this.options.details = value;
    }

    get icon() {
        return this.options.icon;
    }

    set icon(value) {
        this.options.icon = value;
    }

    get departments() {
        return this.options.departments;
    }

    set departments(value) {
        this.options.departments = value;
    }

}