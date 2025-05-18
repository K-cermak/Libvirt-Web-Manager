const domainList = document.querySelector('.domainList');
var selectedDomain = null;
var selectedAction = null;

document.addEventListener("DOMContentLoaded", function () {
    refreshDomainList(false);
    setStateButtons();
});

document.querySelector(".refreshButton").addEventListener("click", function () {
    refreshDomainList(true);
});

function refreshDomainList(manualRefresh) {
    axios.get('/api/list', {})
        .then(function (response) {
            domainList.innerHTML = '';

            for (const domain in response.data.data) {
                const domainData = response.data.data[domain];
                const listItem = createListItem(domain, domainData);
                domainList.innerHTML += listItem;

                if (domain == selectedDomain) {
                    document.querySelector('.domainName').innerText = domain;
                    document.querySelector('.domainDetails').classList.remove('d-none');
                    document.querySelector('.detailState').innerHTML = "<span class='badge bg-" + getBadge(domainData.state) + "'>" + domainData.state + "</span>";
                    document.querySelector('.detailMemory').innerHTML = formatMemory(domainData.memory);
                    document.querySelector('.detailCpus').innerText = domainData.cpus;
                }
            }

            if (response.data.data.length === 0) {
                domainList.innerHTML = '<li class="list-group-item">No domains loaded</li>';
            }

            if (manualRefresh) {
                genFlashMessage("Data refreshed successfully", "success", 5000);
            }
            setEvents();

        })
        .catch(function (error) {
            genFlashMessage("ERROR: An error occurred while trying to refresh data", "error", 5000);
            domainList.innerHTML = '<li class="list-group-item">No domains loaded</li>';
        });
}

function createListItem(domainName, domainData) {
    return `
                <li class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong>${domainName}</strong><br>
                            State: <span class="stateInfo badge bg-${getBadge(domainData.state)}">${domainData.state}</span><br>
                            Memory: <span class="stateMemory badge bg-secondary">${formatMemory(domainData.memory)}</span><br>
                            CPUs: <span class="stateCpus badge bg-secondary">${domainData.cpus}</span><br>
                        </div>
                        <div class="d-flex align-items-center">
                            <button class="btn btn-secondary settingsButton" domainId="${domainName}"><i class="bi bi-gear"></i> Settings</button>
                        </div>
                    </div>
                </li>
            `;
}

function getBadge(state) {
    switch (state) {
        case 'nostate':
            return 'secondary';
        case 'running':
            return 'success';
        case 'blocked':
            return 'warning';
        case 'paused':
            return 'info';
        case 'shutdown':
            return 'danger';
        case 'shutoff':
            return 'dark';
        case 'crashed':
            return 'danger';
        case 'pmsuspended':
            return 'warning';
        default:
            return 'secondary';
    }
}

function formatMemory(memory) {
    if (memory >= 1048576) {
        return (memory / 1048576).toFixed(2) + ' GB';
    } else {
        return (memory / 1024).toFixed(0) + ' MB';
    }
}

function setEvents() {
    const buttons = document.querySelectorAll('.settingsButton');
    const domainDetails = document.querySelector('.domainDetails');

    buttons.forEach(button => {
        button.addEventListener('click', function () {
            const domainId = this.getAttribute('domainId');
            selectedDomain = domainId;

            document.querySelector('.domainName').innerText = domainId;
            domainDetails.classList.remove('d-none');
            domainDetails.querySelector('.detailState').innerHTML = "<span class='badge bg-" + getBadge(this.parentElement.parentElement.querySelector('.stateInfo').innerText) + "'>" + this.parentElement.parentElement.querySelector('.stateInfo').innerText + "</span>";
            domainDetails.querySelector('.detailMemory').innerHTML = this.parentElement.parentElement.querySelector('.stateMemory').innerText;
            domainDetails.querySelector('.detailCpus').innerText = this.parentElement.parentElement.querySelector('.stateCpus').innerText;
        });
    });
}

function setStateButtons() {
    document.querySelector('.startButton').addEventListener('click', function () {
        startAction("start");
    });
    document.querySelector('.shutdownButton').addEventListener('click', function () {
        startAction("shutdown");
    });
    document.querySelector('.suspendButton').addEventListener('click', function () {
        startAction("suspend");
    });
    document.querySelector('.resumeButton').addEventListener('click', function () {
        startAction("resume");
    });
    document.querySelector('.destroyButton').addEventListener('click', function () {
        startAction("destroy");
    });
}

function startAction(action) {
    selectedAction = action;
    let id = genModal(performActionModal);
    document.querySelector('#' + id + ' .modalAction').innerHTML = selectedAction;
    document.querySelector('#' + id + ' .modalDomain').innerHTML = selectedDomain;
}

function performAction() {
    axios.post('/api/action', {
        action: selectedAction,
        domain: selectedDomain
    })
        .then(function (response) {
            genFlashMessage("Action " + selectedAction + " executed successfully", "success", 5000);
            setTimeout(function () {
                refreshDomainList(false);
            }, 2000);
        })
        .catch(function (error) {
            genFlashMessage("ERROR: An error occurred while trying to execute action " + selectedAction, "error", 5000);
        });
}



plugDriveModalId = null;
document.querySelector(".plugDrive").addEventListener("click", function () {
    plugDriveModalId = genModal(plugDriveModal);
});

function createDrive() {
    const drivePath = document.querySelector("#" + plugDriveModalId + " #drivePath").value;
    const driveName = document.querySelector("#" + plugDriveModalId + " #driveName").value;
    const driveType = document.querySelector("#" + plugDriveModalId + " #driveType").value;

    if (drivePath === "" || driveName === "") {
        genFlashMessage("ERROR: Please fill all fields", "error", 5000);
        return;
    }

    axios.post('/api/disks', {
        domain: selectedDomain,
        drivePath: drivePath,
        driveName: driveName,
        driveType: driveType
    })
        .then(function (response) {
            genFlashMessage("Drive " + driveName + " plugged successfully", "success", 5000);
            setTimeout(function () {
                refreshDomainList(false);
            }, 2000);
            closeModal(plugDriveModalId);
        })
        .catch(function (error) {
            genFlashMessage("ERROR: An error occurred while trying to plug drive " + driveName, "error", 5000);
        });
}

document.querySelector(".unplugDrive").addEventListener("click", function () {
    let id = genModal(unplugDrive);

    axios.get('/api/disks?domain=' + selectedDomain, {})
        .then(function (response) {
            const unplugContent = document.querySelector('#' + id + ' .unplugContent');
            unplugContent.innerHTML = '';

            for (const disk in response.data.data) {
                const listItem = `
                            <li class="list-group-item mt-2">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong>${response.data.data[disk].name}</strong><br>
                                        <span class="diskPath">${response.data.data[disk].path}</span><br>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <button class="btn btn-primary unplugDiskButton" diskName="${response.data.data[disk].name}"><i class="bi bi-database-slash"></i> Unplug</button>
                                    </div>
                                </div>
                            </li>
                        `;
                unplugContent.innerHTML += listItem;
            }

            if (response.data.data.length === 0) {
                unplugContent.innerHTML = '<li class="list-group-item">No disks found</li>';
            }

            setUnplugEvents(id);
        })
        .catch(function (error) {
            genFlashMessage("ERROR: An error occurred while trying to get data", "error", 5000);
            return;
        });
});

function setUnplugEvents(modalId) {
    const unplugButtons = document.querySelectorAll('#' + modalId + ' .unplugDiskButton');

    unplugButtons.forEach(button => {
        button.addEventListener('click', function () {
            const diskName = this.getAttribute('diskName');
            axios.delete('/api/disks?domain=' + selectedDomain + '&disk=' + diskName, {})
                .then(function (response) {
                    genFlashMessage("Disk " + diskName + " unplugged successfully", "success", 5000);
                    setTimeout(function () {
                        refreshDomainList(false);
                    }, 2000);
                    closeModal(modalId);
                })
                .catch(function (error) {
                    genFlashMessage("ERROR: An error occurred while trying to unplug disk " + diskName, "error", 5000000);
                });
        });
    });
}



var performActionModal = {
    global: {
        closable: true,
        size: "md",
        scrollable: true,
        position: "center",
    },
    header: {
        title: "Perform action",
        closeButton: true,
    },
    main: {
        content: "Are you sure you want to perform <span class='modalAction fw-bold'></span> on <span class='modalDomain fw-bold'></span>?",
    },
    footer: {
        buttons: {
            close: {
                text: "Cancel",
                type: "secondary",
                function: "close",
            },
            function: {
                text: "Perform",
                type: "primary",
                function: "function",
                dataset: function () {
                    performAction();
                    closeModal("selector");
                },
            },
        },
    },
}

var unplugDrive = {
    global: {
        closable: true,
        size: "md",
        scrollable: true,
        position: "center",
    },
    header: {
        title: "Unplug Drive",
        closeButton: true,
    },
    main: {
        content: `
                <div class="unplugContent"></div>
                `,
    },
    footer: {
        buttons: {
            function: {
                text: "Close",
                type: "secondary",
                function: "close",
                dataset: function () {
                    closeModal("selector");
                },
            },
        },
    },
}

var plugDriveModal = {
    global: {
        closable: true,
        size: "md",
        scrollable: true,
        position: "center",
    },
    header: {
        title: "Plug Drive",
        closeButton: true,
    },
    main: {
        content: `

                <div class="mb-3">
                    <label for="driveName" class="form-label">Dev Name</label>
                    <input type="text" class="form-control" id="driveName" placeholder="sdc">
                </div>

                <div class="mb-3">
                    <label for="drivePath" class="form-label">Drive Location</label>
                    <input type="text" class="form-control" id="drivePath" placeholder="Drive Location">
                </div>

                <div class="mb-3">
                    <label for="driveType" class="form-label">Drive Type</label>
                    <select class="form-select" id="driveType">
                        <option value="usb">USB</option>
                        <option value="virtio">VirtIO</option>
                    </select>
                </div>`,
    },

    footer: {
        buttons: {
            function: {
                text: "Plug",
                type: "primary",
                function: "function",
                dataset: function () {
                    createDrive();
                },
            },
        },
    },
}