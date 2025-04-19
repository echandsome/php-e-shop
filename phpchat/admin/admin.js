const aside = document.querySelector('aside'), main = document.querySelector('main'), header = document.querySelector('header');
const asideStyle = window.getComputedStyle(aside);
if (localStorage.getItem('admin_menu') == 'closed') {
    aside.classList.add('closed', 'responsive-hidden');
    main.classList.add('full');
    header.classList.add('full');
}
document.querySelector('.responsive-toggle').onclick = event => {
    event.preventDefault();
    if (asideStyle.display == 'none') {
        aside.classList.remove('closed', 'responsive-hidden');
        main.classList.remove('full');
        header.classList.remove('full');
        localStorage.setItem('admin_menu', '');
    } else {
        aside.classList.add('closed', 'responsive-hidden');
        main.classList.add('full');
        header.classList.add('full');
        localStorage.setItem('admin_menu', 'closed');
    }
};
document.querySelectorAll('.tabs a').forEach((element, index) => {
    element.onclick = event => {
        event.preventDefault();
        document.querySelectorAll('.tabs a').forEach(element => element.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach((element2, index2) => {
            if (index == index2) {
                element.classList.add('active');
                element2.style.display = 'block';
            } else {
                element2.style.display = 'none';
            }
        });
    };
});
if (document.querySelector('.filters a')) {
    let filtersList = document.querySelector('.filters .list');
    let filtersListStyle = window.getComputedStyle(filtersList);
    document.querySelector('.filters a').onclick = event => {
        event.preventDefault();
        if (filtersListStyle.display == 'none') {
            filtersList.style.display = 'flex';
        } else {
            filtersList.style.display = 'none';
        }
    };
    document.onclick = event => {
        if (!event.target.closest('.filters')) {
            filtersList.style.display = 'none';
        }
    };
}
document.querySelectorAll('.msg').forEach(element => {
    element.querySelector('.fa-times').onclick = () => {
        element.remove();
        history.replaceState && history.replaceState(null, '', location.pathname + location.search.replace(/[\?&]success_msg=[^&]+/, '').replace(/^&/, '?') + location.hash);
        history.replaceState && history.replaceState(null, '', location.pathname + location.search.replace(/[\?&]error_msg=[^&]+/, '').replace(/^&/, '?') + location.hash);
    };
});
if (location.search.includes('success_msg') || location.search.includes('error_msg')) {
    history.replaceState && history.replaceState(null, '', location.pathname + location.search.replace(/[\?&]success_msg=[^&]+/, '').replace(/^&/, '?') + location.hash);
    history.replaceState && history.replaceState(null, '', location.pathname + location.search.replace(/[\?&]error_msg=[^&]+/, '').replace(/^&/, '?') + location.hash);
}
document.body.addEventListener('click', event => {
    if (!event.target.closest('.multiselect')) {
        document.querySelectorAll('.multiselect .list').forEach(element => element.style.display = 'none');
    } 
});
document.querySelectorAll('.multiselect').forEach(element => {
    let updateList = () => {
        element.querySelectorAll('.item').forEach(item => {
            element.querySelectorAll('.list span').forEach(newItem => {
                if (item.dataset.value == newItem.dataset.value) {
                    newItem.style.display = 'none';
                }
            });
            item.querySelector('.remove').onclick = () => {
                element.querySelector('.list span[data-value="' + item.dataset.value + '"]').style.display = 'flex';
                item.querySelector('.remove').parentElement.remove();
            };
        });
        if (element.querySelectorAll('.item').length > 0) {
            element.querySelector('.search').placeholder = '';
        }
    };
    element.onclick = () => element.querySelector('.search').focus();
    element.querySelector('.search').onfocus = () => element.querySelector('.list').style.display = 'flex';
    element.querySelector('.search').onclick = () => element.querySelector('.list').style.display = 'flex';
    element.querySelector('.search').onkeyup = () => {
        element.querySelector('.list').style.display = 'flex';
        element.querySelectorAll('.list span').forEach(item => {
            item.style.display = item.innerText.toLowerCase().includes(element.querySelector('.search').value.toLowerCase()) ? 'flex' : 'none';
        });
        updateList();
    };
    element.querySelectorAll('.list span').forEach(item => item.onclick = () => {
        item.style.display = 'none';
        let html = `
            <span class="item" data-value="${item.dataset.value}">
                <i class="remove">&times;</i>${item.innerText}
                <input type="hidden" name="${element.dataset.name}" value="${item.dataset.value}">    
            </span>
        `;
        if (element.querySelector('.item')) {
            let ele = element.querySelectorAll('.item');
            ele = ele[ele.length-1];
            ele.insertAdjacentHTML('afterend', html);                          
        } else {
            element.insertAdjacentHTML('afterbegin', html);
        }
        element.querySelector('.search').value = '';
        updateList();
    });
    updateList();
});
document.querySelectorAll('.update-status').forEach(element => element.onclick = event => {
    event.preventDefault();
    element.closest('.dropdown').querySelector('.profile-img i').className = element.dataset.status.toLowerCase();
    fetch('api.php?ajax=true&action=update_status&status=' + element.dataset.status);
});
const originalTitle = document.title;
const updateInfo = () => {
    fetch('api.php?ajax=true&action=info', { cache: 'no-store' }).then(response => response.json()).then(data => {
        if (parseInt(data.messages_total) > parseInt(document.querySelector('.messages-total').innerHTML)) {
            let audio = new Audio('../notification.ogg');
            audio.volume = 0.5;
            audio.play();
            document.title = data.messages_total + ' New Messages';
        } else if (parseInt(data.messages_total) < parseInt(document.querySelector('.messages-total').innerHTML)) {
            document.title = originalTitle;
        }
        document.querySelector('.users-online-total').innerHTML = data.users_online_total.toLocaleString();
        document.querySelector('.messages-total').innerHTML = data.messages_total.toLocaleString();
        document.querySelector('.requests-total').innerHTML = data.requests_total.toLocaleString();
        document.querySelector('header .profile-img i').className = data.account_status.toLowerCase();
    });
};
setInterval(updateInfo, general_info_refresh_rate);
const initUsersOnline = () => {
    let userHandler = () => {
        document.querySelectorAll('.users-online .list .users .user').forEach(element => element.onclick = event => {
            event.preventDefault();
            document.querySelectorAll('.users-online .list .users .user').forEach(element => element.classList.remove('selected'));
            element.classList.add('selected');
            document.querySelector('.users-online .info').innerHTML = `
            <div class="profile-img">
                ${element.querySelector('.profile-img').innerHTML}
            </div>
            <div class="actions">
                ${element.dataset.id != account_id ? '<a href="#" class="btn message">Message</a>' : ''}
                ${element.dataset.edit == 'true' ? '<a href="account.php?id=' + element.dataset.id + '" class="btn alt">Edit User</a>' : ''}
            </div>
            <div class="items">
                <div class="item">
                    <h5>Name</h5>
                    <p>${element.querySelector('h3').innerHTML}</p>
                </div>
                <div class="item">
                    <h5>Status</h5>
                    <p>${element.dataset.status}</p>
                </div>
                <div class="item">
                    <h5>Departments</h5>
                    <p>${element.dataset.departments}</p>
                </div>
                <div class="item">
                    <h5>Last Seen</h5>
                    <p>${element.querySelector('p').innerHTML}</p>
                </div>
                <div class="item">
                    <h5>Email</h5>
                    <p>${element.dataset.email}</p>
                </div>
                <div class="item">
                    <h5>Role</h5>
                    <p>${element.dataset.role}</p>
                </div>
                <div class="item">
                    <h5>Registered</h5>
                    <p>${element.dataset.registered}</p>
                </div>
                <div class="item">
                    <h5>IP</h5>
                    <p>${element.dataset.ip}</p>
                </div>
                <div class="item">
                    <h5>User Agent</h5>
                    <p>${element.dataset.useragent}</p>
                </div>
            </div>
            `;
            if (document.querySelector('.users-online .message')) {
                document.querySelector('.users-online .message').onclick = event => {
                    event.preventDefault();
                    fetch('api.php?ajax=true&action=conversation_create&id=' + element.dataset.id, { cache: 'no-store' }).then(response => response.json()).then(data => {
                        if (!data.error) {
                            location.href = data.url;
                        }
                    });
                };
            }
        });
    };
    userHandler();
    document.querySelector('.search').onkeyup = () => {
        if (document.querySelector('.search').value == '') {
            document.querySelectorAll('.users-online .list .users .user').forEach(element => element.style.display = 'flex');
        } else {
            document.querySelectorAll('.users-online .list .users .user').forEach(element => {
                if (element.querySelector('h3').innerHTML.toLowerCase().includes(document.querySelector('.search').value.toLowerCase())) {
                    element.style.display = 'flex';
                } else {
                    element.style.display = 'none';
                }
            });
        }
    };
    setInterval(() => {
        if (document.querySelector('.search').value == '') {
            fetch('users_online.php?ajax=true', { cache: 'no-store' }).then(response => response.text()).then(html => {
                let doc = (new DOMParser()).parseFromString(html, 'text/html');
                let selectedId = document.querySelector('.users-online .list .users .user.selected') ? document.querySelector('.users-online .list .users .user.selected').dataset.id : null;
                document.querySelector('.users-online .list .users').innerHTML = doc.querySelector('.users-online .list .users').innerHTML;
                if (selectedId != null && document.querySelector('.users-online .list .users .user[data-id="' + selectedId + '"]')) {
                    document.querySelector('.users-online .list .users .user[data-id="' + selectedId + '"]').classList.add('selected');
                }
                document.querySelector('.content-title').innerHTML = doc.querySelector('.content-title').innerHTML;
                document.title = doc.querySelector('.content-title h2').innerHTML;
                userHandler();
            });    
        }
    }, users_online_refresh_rate);
};
const initRequests = () => {
    let userHandler = () => {
        document.querySelectorAll('.requests .list .users .user').forEach(element => element.onclick = event => {
            event.preventDefault();
            document.querySelectorAll('.requests .list .users .user').forEach(element => element.classList.remove('selected'));
            element.classList.add('selected');
            document.querySelector('.requests .info').innerHTML = `
            <div class="profile-img">
                ${element.querySelector('.profile-img').innerHTML}
            </div>
            <div class="actions">
                <a href="#" class="btn accept-request">Accept</a>
                ${!element.dataset.transferMethod ? '<a href="#" class="btn alt delete-request">Delete</a>' : ''}
            </div>
            <div class="items">
                ${element.dataset.transferMethod ? '<div class="item"><h5>Transfer Method</h5><p>' + element.dataset.transferMethod + '</p></div>' : ''}
                ${element.dataset.transferFrom ? '<div class="item"><h5>Transfer From</h5><p>' + element.dataset.transferFrom + '</p></div>' : ''}
                ${element.dataset.transferReason ? '<div class="item"><h5>Transfer Reason</h5><p>' + element.dataset.transferReason + '</p></div>' : ''}
                <div class="item">
                    <h5>Name</h5>
                    <p>${element.querySelector('h3').innerHTML}</p>
                </div>
                <div class="item">
                    <h5>Status</h5>
                    <p>${element.dataset.status}</p>
                </div>
                <div class="item">
                    <h5>Departments</h5>
                    <p>${element.dataset.departments}</p>
                </div>
                <div class="item">
                    <h5>Last Seen</h5>
                    <p>${element.querySelector('p').innerHTML}</p>
                </div>
                <div class="item">
                    <h5>Email</h5>
                    <p>${element.dataset.email}</p>
                </div>
                <div class="item">
                    <h5>Role</h5>
                    <p>${element.dataset.role}</p>
                </div>
                <div class="item">
                    <h5>Registered</h5>
                    <p>${element.dataset.registered}</p>
                </div>
                <div class="item">
                    <h5>IP</h5>
                    <p>${element.dataset.ip}</p>
                </div>
                <div class="item">
                    <h5>User Agent</h5>
                    <p>${element.dataset.useragent}</p>
                </div>
            </div>
            `;
            document.querySelector('.requests .accept-request').onclick = event => {
                event.preventDefault();
                fetch('api.php?ajax=true&action=request&id=' + element.dataset.id + (element.dataset.transferMethod ? '&is_transfer=1&conversation_id=' + element.dataset.conversationId : ''), { cache: 'no-store' }).then(response => response.json()).then(data => {
                    if (!data.error) {
                        location.href = data.url;
                    } else {
                        alert(data.error);
                    }
                });
            };
            if (document.querySelector('.requests .delete-request')) {
                document.querySelector('.requests .delete-request').onclick = event => {
                    event.preventDefault();
                    fetch('api.php?ajax=true&action=request_delete&id=' + element.dataset.id, { cache: 'no-store' }).then(response => response.json()).then(data => {
                        if (!data.error) {
                            element.remove();
                            document.querySelector('.requests .info').innerHTML = '';
                        }
                    });
                };
            }
        });
    };
    userHandler();
    document.querySelector('.search').onkeyup = () => {
        if (document.querySelector('.search').value == '') {
            document.querySelectorAll('.requests .list .users .user').forEach(element => element.style.display = 'flex');
        } else {
            document.querySelectorAll('.requests .list .users .user').forEach(element => {
                if (element.querySelector('h3').innerHTML.toLowerCase().includes(document.querySelector('.search').value.toLowerCase())) {
                    element.style.display = 'flex';
                } else {
                    element.style.display = 'none';
                }
            });
        }
    };
    setInterval(() => {
        if (document.querySelector('.search').value == '') {
            fetch('requests.php?ajax=true', { cache: 'no-store' }).then(response => response.text()).then(html => {
                let doc = (new DOMParser()).parseFromString(html, 'text/html');
                let selectedId = document.querySelector('.requests .list .users .user.selected') ? document.querySelector('.requests .list .users .user.selected').dataset.id : null;
                document.querySelector('.requests .list .users').innerHTML = doc.querySelector('.requests .list .users').innerHTML;
                if (selectedId != null && document.querySelector('.requests .list .users .user[data-id="' + selectedId + '"]')) {
                    document.querySelector('.requests .list .users .user[data-id="' + selectedId + '"]').classList.add('selected');
                }
                document.querySelector('.content-title').innerHTML = doc.querySelector('.content-title').innerHTML;
                document.title = doc.querySelector('.content-title h2').innerHTML;
                userHandler();
            });    
        }
    }, requests_refresh_rate);
};
const initConversations = (emojiHexList, presetList, attachmentFileTypes) => {
    let conversationId = null;
    let userHandler = () => {
        document.querySelectorAll('.conversations .list .users .user').forEach(element => element.onclick = event => {
            event.preventDefault();
            document.querySelectorAll('.conversations .list .users .user').forEach(element => element.classList.remove('selected'));
            element.classList.add('selected');
            fetch('api.php?ajax=true&action=conversation&id=' + element.dataset.id, { cache: 'no-store' }).then(response => response.json()).then(data => {
                if (!data.error) {
                    if (window.getComputedStyle(document.querySelector('.conversations')).flexFlow.includes('column')) {
                        document.querySelector('.conversations .list').style.display = 'none';
                    }
                    document.querySelector('.conversations .messages').style.display = 'block';
                    document.querySelector('.conversations .info').style.display = 'none';
                    conversationId = element.dataset.id;
                    let html = `
                    <div class="chat-header">
                        <div class="profile-img">
                            ${element.querySelector('.profile-img').innerHTML}
                        </div>
                        <div class="details">
                            <h3>${element.querySelector('.details h3').innerHTML}</h3>
                            <p>${element.dataset.lastseen}</p>
                        </div>
                        <div class="actions">
                            <a href="#" class="transfer-conversation" title="Transfer Conversation"><i class="fa-solid fa-arrow-right-arrow-left"></i></a>
                            <a href="#" class="archive-conversation" title="Archive Conversation"><i class="fa-solid fa-clock-rotate-left"></i></a>
                            <a href="#" class="view-profile" title="View Profile"><i class="fa-solid fa-user"></i></a>
                            <a href="#" class="close-conversation" title="Close Conversation"><i class="fa-solid fa-xmark"></i></a>
                        </div>
                    </div>
                    <div class="chat-messages scroll">
                        <p class="date">You're now chatting with ${data['account_' + data.which + '_full_name']}!</p>
                    `;
                    if (data.messages) {
                        Object.entries(data.messages).forEach(([ date, messages ]) => {  
                            let currentDate = new Date().toLocaleDateString(undefined, { year: '2-digit', month: '2-digit', day: '2-digit' });
                            html += `<p class="date">${currentDate==date?'Today':date}</p>`;
                            messages.forEach(message => {
                                html += `
                                <div class="chat-message${account_id==message['account_id']?'':' alt'}" title="${message['submit_date']}">
                                    ${message['msg']}
                                </div>
                                `;
                                let attachments = message['attachments'].split(',');
                                attachments = attachments.filter(item => item);
                                if (attachments.length) {
                                    html += `
                                    <div class="chat-message-attachments${account_id==message['account_id']?'':' alt'}">
                                        ${attachments.length} Attachment${attachments.length > 1 ? 's' : ''}
                                    </div>
                                    <div class="chat-message-attachments-links">
                                        ${attachments.map(i => '<a href="../' + i + '" download></a>').join('')}
                                    </div>
                                    `;
                                }
                            });
                        });
                    }
                    html += `
                    </div>
                    <div class="chat-attachments"></div>
                    <form action="" method="post" class="chat-input-message" autocomplete="off">
                        <textarea name="msg" placeholder="Message" class="scroll"></textarea>
                        <input type="hidden" name="id" value="${data['id']}">
                        <input type="file" name="files[]" class="files" accept="${attachmentFileTypes}" multiple>
                        <div class="actions">
                            <button type="submit" title="Send Message">
                                <i class="fa-regular fa-paper-plane"></i>
                            </button>
                            ${attachments_enabled ? '<a href="#" class="attach-files" title="Attach File(s)"><i class="fa-solid fa-paperclip"></i></a>' : ''}
                            <div class="view-presets">
                                <i class="fa-regular fa-comment" title="View Message Presets"></i>
                                <span class="preset-list scroll">
                                ${presetList.map(i => '<span>' + i + '</span>').join('')}
                                </span>    
                            </div> 
                            <div class="view-emojis">
                                <i class="fa-regular fa-face-smile" title="View Emojis"></i>
                                <span class="emoji-list scroll">
                                ${emojiHexList.split(',').map(i => '<span>&#x' + i + ';</span>').join('')}
                                </span>    
                            </div>                       
                        </div>
                    </form>
                    `;
                    document.querySelector('.conversations .messages').innerHTML = html;
                    let chatInputMsg = document.querySelector('.chat-input-message');
                    if (chatInputMsg) {
                        if (document.querySelector('.chat-messages').lastElementChild) {
                            document.querySelector('.chat-messages').scrollTop = document.querySelector('.chat-messages').lastElementChild.offsetTop;
                        }
                        chatInputMsg.onsubmit = event => {
                            event.preventDefault();
                            let chatMsgValue = chatInputMsg.querySelector('textarea').value;
                            if (chatMsgValue) {
                                chatInputMsg.querySelector('textarea').value = chatInputMsg.querySelector('textarea').value.replace(/([\u2700-\u27BF]|[\uE000-\uF8FF]|\uD83C[\uDC00-\uDFFF]|\uD83D[\uDC00-\uDFFF]|[\u2011-\u26FF]|\uD83E[\uDD10-\uDDFF])/g, match => '&#x' + match.codePointAt(0).toString(16).toUpperCase() + ';');
                                fetch('api.php?ajax=true&action=message', { 
                                    cache: 'no-store',
                                    method: 'POST',
                                    body: new FormData(chatInputMsg)
                                }).then(response => response.json()).then(data => {
                                    if (!data.msg.includes('Success')) {
                                        console.log(data.msg);
                                    }
                                });
                                let chatMsg = document.createElement('div');
                                chatMsg.classList.add('chat-message');
                                chatMsg.textContent = chatMsgValue;
                                chatMsg.innerHTML = chatMsg.innerHTML.replace(/\n\r?/g, '<br>');
                                document.querySelector('.chat-messages').insertAdjacentElement('beforeend', chatMsg);
                                chatInputMsg.querySelector('textarea').value = '';
                                document.querySelector('.chat-messages').scrollTop = chatMsg.offsetTop;
                                document.querySelector('.chat-attachments').innerHTML = '';
                                chatInputMsg.querySelector('.files').value = '';
                                updateConversationsList();
                            }
                            chatInputMsg.querySelector('textarea').focus();
                        };
                        chatInputMsg.querySelector('.files').onchange = event => {
                            document.querySelector('.chat-attachments').innerHTML = '';
                            [...event.target.files].forEach(file => {
                                let attachmentLink = document.createElement('div');
                                attachmentLink.innerText = file.name;
                                document.querySelector('.chat-attachments').appendChild(attachmentLink);
                            });
                            let removeAttachmentsLink = document.createElement('a');
                            removeAttachmentsLink.innerHTML = '<i class="fa-solid fa-xmark"></i>';
                            document.querySelector('.chat-attachments').appendChild(removeAttachmentsLink);
                            removeAttachmentsLink.onclick = event => {
                                event.preventDefault();
                                document.querySelector('.chat-attachments').innerHTML = '';
                                chatInputMsg.querySelector('.files').value = '';
                            };
                        };
                        chatInputMsg.querySelector('textarea').onkeydown = event => {
                            if (event.key == 'Enter' && !event.shiftKey) {
                                event.preventDefault();
                                chatInputMsg.dispatchEvent(new Event('submit'));
                            }
                        };
                    }
                    document.querySelectorAll('.chat-message-attachments').forEach(element => element.onclick = () => {
                        element.nextElementSibling.querySelectorAll('a').forEach(element => element.click());
                    });
                    document.querySelector('.actions .view-emojis i').onclick = event => {
                        event.preventDefault();
                        document.querySelector('.actions .emoji-list').classList.toggle('open');
                    };
                    document.querySelectorAll('.actions .emoji-list span').forEach(element => element.onclick = () => {
                        chatInputMsg.querySelector('textarea').value += element.innerText;
                        document.querySelector('.actions .emoji-list').classList.remove('open');
                        chatInputMsg.querySelector('textarea').focus();
                    });
                    document.querySelector('.actions .view-presets i').onclick = event => {
                        event.preventDefault();
                        document.querySelector('.actions .preset-list').classList.toggle('open');
                    };
                    document.querySelectorAll('.actions .preset-list span').forEach(element => element.onclick = () => {
                        chatInputMsg.querySelector('textarea').value = element.innerText;
                        document.querySelector('.actions .preset-list').classList.remove('open');
                        chatInputMsg.querySelector('textarea').focus();
                    });
                    document.querySelector('.actions .close-conversation').onclick = event => {
                        event.preventDefault();
                        conversationId = null;
                        document.querySelector('.conversations .messages').innerHTML = '';
                        document.querySelectorAll('.conversations .list .users .user').forEach(element => element.classList.remove('selected'));
                        document.querySelector('.conversations .list').style.display = 'block';
                    };
                    document.querySelector('.actions .archive-conversation').onclick = event => {
                        event.preventDefault();
                        if (confirm('Are you sure you want to archive this conversation? You will no longer be able to reply to it.')) {
                            fetch('api.php?ajax=true&action=conversation_archive&id=' + conversationId, { cache: 'no-store' });
                            element.remove();
                            document.querySelector('.actions .close-conversation').click();
                        }
                    };
                    if (document.querySelector('.actions .attach-files')) {
                        document.querySelector('.actions .attach-files').onclick = event => {
                            event.preventDefault();
                            chatInputMsg.querySelector('.files').click();
                        };
                    }
                    document.querySelector('.actions .view-profile').onclick = event => {
                        event.preventDefault();
                        fetch('api.php?ajax=true&action=account&id=' + element.dataset.accountid, { cache: 'no-store' }).then(response => response.json()).then(data => {
                            if (!data.error) {
                                document.querySelector('.conversations .messages').style.display = 'none';
                                document.querySelector('.conversations .info').style.display = 'flex';
                                document.querySelector('.conversations .info').innerHTML = `
                                <a href="#" class="close-profile"><i class="fa-solid fa-xmark"></i></a>
                                <div class="profile-img">
                                    ${element.querySelector('.profile-img').innerHTML}
                                </div>
                                <div class="actions">
                                    ${data.edit ? '<a href="account.php?id=' + data.id + '" class="btn alt">Edit User</a>' : ''}
                                </div>
                                <div class="items">
                                    <div class="item">
                                        <h5>Name</h5>
                                        <p>${element.querySelector('h3').innerHTML}</p>
                                    </div>
                                    <div class="item">
                                        <h5>Status</h5>
                                        <p>${data.status}</p>
                                    </div>
                                    <div class="item">
                                        <h5>Last Seen</h5>
                                        <p>${data.last_seen}</p>
                                    </div>
                                    <div class="item">
                                        <h5>Email</h5>
                                        <p>${data.email}</p>
                                    </div>
                                    <div class="item">
                                        <h5>Role</h5>
                                        <p>${data.role}</p>
                                    </div>
                                    <div class="item">
                                        <h5>Registered</h5>
                                        <p>${data.registered}</p>
                                    </div>
                                    <div class="item">
                                        <h5>IP</h5>
                                        <p>${data.ip}</p>
                                    </div>
                                    <div class="item">
                                        <h5>User Agent</h5>
                                        <p>${data.user_agent}</p>
                                    </div>
                                </div>
                                `;
                                document.querySelector('.close-profile').onclick = event => {
                                    event.preventDefault();
                                    document.querySelector('.conversations .messages').style.display = 'block';
                                    document.querySelector('.conversations .info').style.display = 'none';
                                };
                            }
                        });
                    };
                    document.querySelector('.actions .transfer-conversation').onclick = event => {
                        event.preventDefault();
                        fetch('api.php?ajax=true&action=get_online_ops', { cache: 'no-store' }).then(response => response.json()).then(data => {
                            if (!data.error) {
                                document.querySelector('.conversations .messages').style.display = 'none';
                                document.querySelector('.conversations .info').style.display = 'flex';
                                document.querySelector('.conversations .info').innerHTML = `
                                <a href="#" class="close-profile"><i class="fa-solid fa-xmark"></i></a>
                                <form action="post" class="transfer-user-form">
                                    <div class="form responsive-width-100">
                                        <label for="transfer_to">Transfer To Operator</label>
                                        <select id="transfer_to" name="transfer_to">
                                            <option value="">(none)</option>
                                            ${data.accounts.map(i => '<option value="' + i.id + '">' + i.full_name + '</option>').join('')}
                                        </select>
                                        <label for="transfer_to_department">Transfer To Department</label>
                                        <select id="transfer_to_department" name="transfer_to_department">
                                            <option value="">(none)</option>
                                            ${data.departments.length == 0 ? '<option value="">No Departments</option>' : ''}
                                            ${data.departments.map(i => '<option value="' + i.id + '">' + i.title + '</option>').join('')}
                                        </select>
                                        <label for="transfer_reason">Transfer Reason</label>
                                        <input type="text" id="transfer_reason" name="transfer_reason" placeholder="Transfer Reason">
                                    </div>
                                    <div style="padding:0 20px">
                                        <div class="output"></div>
                                        <button type="submit" class="btn">Transfer</button>
                                    </div>
                                </form>
                                `;
                                document.querySelector('.close-profile').onclick = event => {
                                    event.preventDefault();
                                    document.querySelector('.conversations .messages').style.display = 'block';
                                    document.querySelector('.conversations .info').style.display = 'none';
                                };
                                document.querySelector('.transfer-user-form').onsubmit = event => {
                                    event.preventDefault();
                                    if (!document.querySelector('.transfer-user-form #transfer_to').value && !document.querySelector('.transfer-user-form #transfer_to_department').value) {
                                        document.querySelector('.transfer-user-form .output').innerHTML = 'Please select an operator or department to transfer to!<br><br>';
                                        return;
                                    }
                                    if (confirm('Are you sure you want to transfer this conversation?')) {
                                        fetch('api.php?ajax=true&action=transfer&id=' + conversationId, { 
                                            cache: 'no-store',
                                            method: 'POST',
                                            body: new FormData(document.querySelector('.transfer-user-form'))
                                        }).then(response => response.json()).then(data => {
                                            if (!data.error) {
                                                element.remove();
                                                document.querySelector('.actions .close-conversation').click();
                                                document.querySelector('.close-profile').click();
                                            } else {
                                                document.querySelector('.transfer-user-form .output').innerHTML = data.msg + '<br><br>';
                                            }
                                        });
                                    }
                                };
                            }
                        });
                    };
                }
            });
        });
    };
    let updateConversationsList = () => {
        if (document.querySelector('.search').value == '') {
            fetch('messages.php?ajax=true', { cache: 'no-store' }).then(response => response.text()).then(html => {
                let doc = (new DOMParser()).parseFromString(html, 'text/html');
                let selectedId = document.querySelector('.conversations .list .users .user.selected') ? document.querySelector('.conversations .list .users .user.selected').dataset.id : null;
                document.querySelector('.conversations .list .users').innerHTML = doc.querySelector('.conversations .list .users').innerHTML;
                if (selectedId != null && document.querySelector('.conversations .list .users .user[data-id="' + selectedId + '"]')) {
                    let selectedUser = document.querySelector('.conversations .list .users .user[data-id="' + selectedId + '"]');
                    document.querySelector('.conversations .list .users .user[data-id="' + selectedId + '"]').classList.add('selected');
                    if (document.querySelector('.chat-header')) {
                        document.querySelector('.chat-header .profile-img i').className = selectedUser.querySelector('.profile-img i').className;
                        document.querySelector('.chat-header .details p').innerHTML = selectedUser.dataset.lastseen;
                    }
                }
                userHandler();
            });    
        } 
    };
    let updateMessages = () => {
        if (conversationId != null && document.querySelector('.chat-messages')) {
            fetch('api.php?ajax=true&action=conversation&id=' + conversationId, { cache: 'no-store' }).then(response => response.json()).then(data => {
                if (!data.error) {
                    let html = `<p class="date">You're now chatting with ${data['account_' + data.which + '_full_name']}!</p>`;
                    let canScroll = true;
                    if (data.messages) {
                        Object.entries(data.messages).forEach(([ date, messages ]) => {  
                            let currentDate = new Date().toLocaleDateString(undefined, { year: '2-digit', month: '2-digit', day: '2-digit' });
                            html += `<p class="date">${currentDate==date?'Today':date}</p>`;
                            messages.forEach(message => {
                                html += `
                                <div class="chat-message${account_id==message['account_id']?'':' alt'}" title="${message['submit_date']}">
                                    ${message['msg']}
                                </div>
                                `;
                                let attachments = message['attachments'].split(',');
                                attachments = attachments.filter(item => item);
                                if (attachments.length) {
                                    html += `
                                    <div class="chat-message-attachments${account_id==message['account_id']?'':' alt'}">
                                        ${attachments.length} Attachment${attachments.length > 1 ? 's' : ''}
                                    </div>
                                    <div class="chat-message-attachments-links">
                                        ${attachments.map(i => '<a href="../' + i + '" download></a>').join('')}
                                    </div>
                                    `;
                                }
                            });
                        });
                    }
                    if (document.querySelector('.chat-messages').lastElementChild && document.querySelector('.chat-messages').scrollHeight - document.querySelector('.chat-messages').scrollTop != document.querySelector('.chat-messages').clientHeight) {
                        canScroll = false;
                    }                    
                    document.querySelector('.chat-messages').innerHTML = html;
                    if (canScroll && document.querySelector('.chat-messages').lastElementChild) {
                        document.querySelector('.chat-messages').scrollTop = document.querySelector('.chat-messages').lastElementChild.offsetTop;
                    }  
                    document.querySelectorAll('.chat-message-attachments').forEach(element => element.onclick = () => {
                        element.nextElementSibling.querySelectorAll('a').forEach(element => element.click());
                    });
                }
            });
        }
    };
    userHandler();
    document.querySelector('.search').onkeyup = () => {
        if (document.querySelector('.search').value == '') {
            document.querySelectorAll('.conversations .list .users .user').forEach(element => element.style.display = 'flex');
        } else {
            document.querySelectorAll('.conversations .list .users .user').forEach(element => {
                if (element.querySelector('h3').innerHTML.toLowerCase().includes(document.querySelector('.search').value.toLowerCase())) {
                    element.style.display = 'flex';
                } else {
                    element.style.display = 'none';
                }
            });
        }
    };
    setInterval(() => {
        updateConversationsList();      
        updateMessages();
    }, conversation_refresh_rate);
    if (document.querySelector('.conversations .list .users .user.selected')) {
        document.querySelector('.conversations .list .users .user.selected').click();
    }
};