document.addEventListener("DOMContentLoaded", function () {

    const API = "admin.php";


    /* =====================================================
       API REQUEST
    ===================================================== */

    async function request(action, data = {}) {

        const formData = new FormData();

        formData.append("action", action);

        Object.keys(data).forEach(function (key) {
            formData.append(key, data[key]);
        });

        try {

            const response = await fetch(API, {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            });

            const text = await response.text();

            let result;

            try {
                result = JSON.parse(text);
            } catch (error) {

                console.error("PHP Response:", text);

                alert(
                    "PHP error occurred.\n\n" +
                    "Please check admin.php."
                );

                return {
                    success: false,
                    data: []
                };
            }

            if (!result.success) {

                console.error(
                    "Admin PHP Error:",
                    result.message
                );

                alert(
                    result.message ||
                    "Something went wrong."
                );
            }

            return result;

        } catch (error) {

            console.error(error);

            alert(
                "Unable to connect to admin.php.\n\n" +
                "Make sure WAMP Apache and MySQL are running."
            );

            return {
                success: false,
                data: []
            };
        }
    }


    /* =====================================================
       SIDEBAR NAVIGATION
    ===================================================== */

    document.querySelectorAll(
        ".nav-link[data-target]"
    ).forEach(function (link) {

        link.addEventListener("click", function (event) {

            event.preventDefault();

            const targetId = link.dataset.target;

            const target =
                document.getElementById(targetId);

            if (target) {

                target.scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });
            }

            document.querySelectorAll(
                ".nav-link[data-target]"
            ).forEach(function (item) {

                item.classList.remove("active");

            });

            link.classList.add("active");

            if (targetId === "notifications") {
                loadNotifications();
            }

        });

    });


    /* =====================================================
       HEADER NOTIFICATIONS
    ===================================================== */

    const notificationLinks =
        document.querySelectorAll(
            ".header-link"
        );

    notificationLinks.forEach(function (link) {

        if (
            link.textContent
                .trim()
                .toLowerCase()
                .includes("notifications")
        ) {

            link.addEventListener(
                "click",
                function (event) {

                    event.preventDefault();

                    const section =
                        document.getElementById(
                            "notifications"
                        );

                    if (section) {

                        section.scrollIntoView({
                            behavior: "smooth",
                            block: "start"
                        });

                    }

                    loadNotifications();

                }
            );

        }

    });


    /* =====================================================
       SIDEBAR TOGGLE
    ===================================================== */

    const sidebarToggle =
        document.getElementById(
            "sidebarToggle"
        );

    const sidebar =
        document.getElementById(
            "sidebar"
        );

    if (
        sidebarToggle &&
        sidebar
    ) {

        sidebarToggle.addEventListener(
            "click",
            function () {

                sidebar.classList.toggle("show");

            }
        );

    }


    /* =====================================================
       DASHBOARD
    ===================================================== */

    async function loadDashboard() {

        const result =
            await request("dashboard");

        if (!result.success) {
            return;
        }

        const data = result.data;

        const values =
            document.querySelectorAll(
                ".stat-value"
            );

        if (values[0])
            values[0].textContent =
                data.total_users ?? 0;

        if (values[1])
            values[1].textContent =
                data.pharmacists ?? 0;

        if (values[2])
            values[2].textContent =
                data.suppliers ?? 0;

        if (values[3])
            values[3].textContent =
                data.medicines ?? 0;

        if (values[4])
            values[4].textContent =
                data.today_orders ?? 0;

        if (values[5])
            values[5].textContent =
                data.pending_approvals ?? 0;

        if (values[6])
            values[6].textContent =
                data.monthly_orders ?? 0;

        if (values[7]) {

            values[7].textContent =
                "LKR " +
                Number(
                    data.revenue ?? 0
                ).toLocaleString(
                    "en-LK",
                    {
                        minimumFractionDigits: 2
                    }
                );

        }

    }


    /* =====================================================
       LOAD USERS
    ===================================================== */

    async function loadUsers() {

        const result =
            await request("users");

        if (!result.success) {
            return;
        }

        const tbody =
            document.querySelector(
                ".user-table tbody"
            );

        if (!tbody) {
            return;
        }

        tbody.innerHTML = "";

        result.data.forEach(function (user) {

            const row =
                document.createElement("tr");

            const status =
                user.status || "Active";

            row.innerHTML = `

                <td>#${escapeHTML(user.user_id)}</td>

                <td>
                    ${escapeHTML(
                        user.full_name || "N/A"
                    )}
                </td>

                <td>
                    ${escapeHTML(
                        user.email || "N/A"
                    )}
                </td>

                <td>
                    ${escapeHTML(
                        user.username || "N/A"
                    )}
                </td>

                <td>
                    ${escapeHTML(
                        user.phone || "N/A"
                    )}
                </td>

                <td>
                    ${escapeHTML(
                        user.address || "N/A"
                    )}
                </td>

                <td>
                    <span class="status ${
                        status === "Active"
                            ? "active"
                            : "inactive"
                    }">
                        ${escapeHTML(status)}
                    </span>
                </td>

                <td>
                    <button
                        class="edit-btn edit-user"
                        data-id="${user.user_id}">
                        Edit
                    </button>
                </td>

                <td>

                    <button
                        class="${
                            status === "Active"
                                ? "deactivate-btn"
                                : "activate-btn"
                        } toggle-user"
                        data-id="${user.user_id}"
                        data-status="${status}">

                        ${
                            status === "Active"
                                ? "Deactivate"
                                : "Activate"
                        }

                    </button>

                </td>

            `;

            tbody.appendChild(row);

        });

    }


    /* =====================================================
       LOAD PHARMACISTS
    ===================================================== */

    async function loadPharmacists() {

        const result =
            await request("pharmacists");

        if (!result.success) {
            return;
        }

        const tbody =
            document.querySelector(
                ".pharmacist-table tbody"
            );

        if (!tbody) {
            return;
        }

        tbody.innerHTML = "";

        result.data.forEach(function (pharmacist) {

            const row =
                document.createElement("tr");

            const status =
                pharmacist.status || "Active";

            row.innerHTML = `

                <td>
                    ${escapeHTML(
                        pharmacist.pharmacist_id ||
                        pharmacist.pharmacist_code ||
                        "N/A"
                    )}
                </td>

                <td>
                    ${escapeHTML(
                        pharmacist.full_name ||
                        "N/A"
                    )}
                </td>

                <td>

                    <strong>Email:</strong>
                    ${escapeHTML(
                        pharmacist.email || "N/A"
                    )}

                    <br>

                    <strong>Phone:</strong>
                    ${escapeHTML(
                        pharmacist.phone || "N/A"
                    )}

                </td>

                <td>
                    ${escapeHTML(
                        pharmacist.username || "N/A"
                    )}
                </td>

                <td>
                    ${escapeHTML(
                        pharmacist.address || "N/A"
                    )}
                </td>

                <td>

                    <span class="status ${
                        status === "Active"
                            ? "active"
                            : "inactive"
                    }">

                        ${escapeHTML(status)}

                    </span>

                </td>

                <td>

                    <button
                        class="edit-btn edit-pharmacist"
                        data-id="${pharmacist.user_id}">
                        Edit
                    </button>

                </td>

                <td>

                    <button
                        class="${
                            status === "Active"
                                ? "deactivate-btn"
                                : "activate-btn"
                        } toggle-pharmacist"

                        data-id="${pharmacist.user_id}"
                        data-status="${status}">

                        ${
                            status === "Active"
                                ? "Deactivate"
                                : "Activate"
                        }

                    </button>

                </td>

            `;

            tbody.appendChild(row);

        });

    }


    /* =====================================================
       LOAD SUPPLIERS
    ===================================================== */

    async function loadSuppliers() {

        const result =
            await request("suppliers");

        if (!result.success) {
            return;
        }

        const tbody =
            document.querySelector(
                ".supplier-table tbody"
            );

        if (!tbody) {
            return;
        }

        tbody.innerHTML = "";

        result.data.forEach(function (supplier) {

            const row =
                document.createElement("tr");

            row.innerHTML = `

                <td>
                    #${escapeHTML(
                        supplier.supplier_id
                    )}
                </td>

                <td>
                    ${escapeHTML(
                        supplier.supplier_name ||
                        "N/A"
                    )}
                </td>

                <td>
                    ${escapeHTML(
                        supplier.contact_person ||
                        "N/A"
                    )}
                </td>

                <td>
                    ${escapeHTML(
                        supplier.phone ||
                        "N/A"
                    )}
                </td>

                <td>
                    ${escapeHTML(
                        supplier.email ||
                        "N/A"
                    )}
                </td>

                <td>
                    ${escapeHTML(
                        supplier.address ||
                        "N/A"
                    )}
                </td>

                <td>

                    <button
                        class="edit-btn edit-supplier"
                        data-id="${supplier.supplier_id}">
                        Edit
                    </button>

                </td>

                <td>

                    <button
                        class="deactivate-btn delete-supplier"
                        data-id="${supplier.supplier_id}">
                        Delete
                    </button>

                </td>

            `;

            tbody.appendChild(row);

        });

    }


    /* =====================================================
       STOCK APPROVALS
    ===================================================== */

    async function loadStockApprovals() {

        const result =
            await request(
                "stock_approvals"
            );

        if (!result.success) {
            return;
        }

        const tbody =
            document.querySelector(
                ".stock-table tbody"
            );

        if (!tbody) {
            return;
        }

        tbody.innerHTML = "";

        result.data.forEach(function (item) {

            const row =
                document.createElement("tr");

            const status =
                item.status || "Pending";

            row.innerHTML = `

                <td>
                    #ST${String(
                        item.request_id
                    ).padStart(3, "0")}
                </td>

                <td>
                    ${escapeHTML(
                        item.medicine_name ||
                        "N/A"
                    )}
                </td>

                <td>
                    ${escapeHTML(
                        item.quantity ||
                        "0"
                    )}
                </td>

                <td>
                    ${formatDateTime(
                        item.requested_at
                    )}
                </td>

                <td>

                    <span class="status ${
                        getStatusClass(status)
                    }">

                        ${escapeHTML(status)}

                    </span>

                </td>

                <td>

                    ${
                        status === "Pending"
                            ? `
                                <button
                                    class="approve-btn approve-stock"
                                    data-id="${item.request_id}">
                                    Approve
                                </button>
                              `
                            : "-"
                    }

                </td>

                <td>

                    ${
                        status === "Pending"
                            ? `
                                <button
                                    class="reject-btn reject-stock"
                                    data-id="${item.request_id}">
                                    Reject
                                </button>
                              `
                            : "-"
                    }

                </td>

            `;

            tbody.appendChild(row);

        });

    }


    /* =====================================================
       SYSTEM NOTIFICATIONS
    ===================================================== */

    async function loadNotifications() {

        const result =
            await request(
                "notifications"
            );

        if (!result.success) {
            return;
        }

        const tbody =
            document.querySelector(
                ".notifications-table tbody"
            );

        if (!tbody) {
            return;
        }

        tbody.innerHTML = "";

        result.data.forEach(function (notification) {

            const status =
                notification.admin_status ||
                "Pending";

            const priority =
                notification.priority ||
                "Low";

            const pharmacistId =
                notification.pharmacist_code ||
                notification.pharmacist_id ||
                "N/A";

            const row =
                document.createElement("tr");

            row.innerHTML = `

                <td>
                    ${escapeHTML(pharmacistId)}
                </td>

                <td>
                    ${escapeHTML(
                        notification.request_type ||
                        "N/A"
                    )}
                </td>

                <td>
                    ${escapeHTML(
                        notification.medicine_name ||
                        "N/A"
                    )}
                </td>

                <td>
                    ${formatDateTime(
                        notification.submitted_date
                    )}
                </td>

                <td>

                    <span class="priority ${
                        priority.toLowerCase()
                    }">

                        ${escapeHTML(priority)}

                    </span>

                </td>

                <td>

                    <span class="status ${
                        getStatusClass(status)
                    }">

                        ${escapeHTML(status)}

                    </span>

                </td>

                <td>

                    <button
                        type="button"
                        class="view-link view-notification"
                        data-id="${notification.notification_id}">
                        View
                    </button>

                    ${
                        status === "Pending"
                            ? `

                                <button
                                    type="button"
                                    class="approve-btn approve-notification"
                                    data-id="${notification.notification_id}">
                                    Approve
                                </button>

                                <button
                                    type="button"
                                    class="reject-btn reject-notification"
                                    data-id="${notification.notification_id}">
                                    Reject
                                </button>

                              `
                            : ""
                    }

                </td>

            `;

            tbody.appendChild(row);

        });

        updateNotificationBadge(
            result.data
        );

    }


    /* =====================================================
       NOTIFICATION BADGE
    ===================================================== */

    function updateNotificationBadge(
        notifications
    ) {

        const pending =
            notifications.filter(function (item) {

                return (
                    (item.admin_status || "Pending")
                    .toLowerCase() === "pending"
                );

            }).length;

        const links =
            document.querySelectorAll(
                ".header-link"
            );

        let notificationLink = null;

        links.forEach(function (link) {

            if (
                link.textContent
                    .trim()
                    .toLowerCase()
                    .includes("notifications")
            ) {

                notificationLink = link;

            }

        });

        if (!notificationLink) {
            return;
        }

        const oldBadge =
            notificationLink.querySelector(
                ".notification-badge"
            );

        if (oldBadge) {
            oldBadge.remove();
        }

        if (pending > 0) {

            const badge =
                document.createElement("span");

            badge.className =
                "notification-badge";

            badge.textContent =
                pending;

            notificationLink.appendChild(
                badge
            );

        }

    }


    /* =====================================================
       APPROVE NOTIFICATION
    ===================================================== */

    document.addEventListener(
        "click",
        async function (event) {

            const button =
                event.target.closest(
                    ".approve-notification"
                );

            if (!button) {
                return;
            }

            const id =
                button.dataset.id;

            if (
                !confirm(
                    "Approve this notification?"
                )
            ) {
                return;
            }

            const result =
                await request(
                    "notification_action",
                    {
                        notification_id: id,
                        status: "Approved"
                    }
                );

            if (result.success) {

                alert(
                    "Notification approved successfully."
                );

                await loadNotifications();
                await loadDashboard();

            }

        }
    );


    /* =====================================================
       REJECT NOTIFICATION
    ===================================================== */

    document.addEventListener(
        "click",
        async function (event) {

            const button =
                event.target.closest(
                    ".reject-notification"
                );

            if (!button) {
                return;
            }

            const id =
                button.dataset.id;

            if (
                !confirm(
                    "Reject this notification?"
                )
            ) {
                return;
            }

            const result =
                await request(
                    "notification_action",
                    {
                        notification_id: id,
                        status: "Rejected"
                    }
                );

            if (result.success) {

                alert(
                    "Notification rejected successfully."
                );

                await loadNotifications();
                await loadDashboard();

            }

        }
    );


    /* =====================================================
       VIEW NOTIFICATION
    ===================================================== */

    document.addEventListener(
        "click",
        async function (event) {

            const button =
                event.target.closest(
                    ".view-notification"
                );

            if (!button) {
                return;
            }

            const id =
                button.dataset.id;

            const result =
                await request(
                    "notifications"
                );

            if (!result.success) {
                return;
            }

            const notification =
                result.data.find(
                    function (item) {

                        return String(
                            item.notification_id
                        ) === String(id);

                    }
                );

            if (!notification) {
                alert(
                    "Notification not found."
                );
                return;
            }

            alert(

                "Pharmacist ID: " +
                (
                    notification.pharmacist_code ||
                    notification.pharmacist_id ||
                    "N/A"
                ) +

                "\n\nRequest Type: " +
                (
                    notification.request_type ||
                    "N/A"
                ) +

                "\nMedicine: " +
                (
                    notification.medicine_name ||
                    "N/A"
                ) +

                "\nPriority: " +
                (
                    notification.priority ||
                    "Low"
                ) +

                "\nStatus: " +
                (
                    notification.admin_status ||
                    "Pending"
                ) +

                "\n\nDetails:\n" +
                (
                    notification.details ||
                    "No details available."
                )

            );

        }
    );


    /* =====================================================
       APPROVE STOCK
    ===================================================== */

    document.addEventListener(
        "click",
        async function (event) {

            const button =
                event.target.closest(
                    ".approve-stock"
                );

            if (!button) {
                return;
            }

            const id =
                button.dataset.id;

            if (
                !confirm(
                    "Approve this stock request?"
                )
            ) {
                return;
            }

            const result =
                await request(
                    "approve_stock",
                    {
                        request_id: id
                    }
                );

            if (result.success) {

                alert(
                    "Stock request approved."
                );

                await loadStockApprovals();
                await loadDashboard();
                await loadNotifications();

            }

        }
    );


    /* =====================================================
       REJECT STOCK
    ===================================================== */

    document.addEventListener(
        "click",
        async function (event) {

            const button =
                event.target.closest(
                    ".reject-stock"
                );

            if (!button) {
                return;
            }

            const id =
                button.dataset.id;

            if (
                !confirm(
                    "Reject this stock request?"
                )
            ) {
                return;
            }

            const result =
                await request(
                    "reject_stock",
                    {
                        request_id: id
                    }
                );

            if (result.success) {

                alert(
                    "Stock request rejected."
                );

                await loadStockApprovals();
                await loadDashboard();
                await loadNotifications();

            }

        }
    );


    /* =====================================================
       USER ACTIVATE / DEACTIVATE
    ===================================================== */

    document.addEventListener(
        "click",
        async function (event) {

            const button =
                event.target.closest(
                    ".toggle-user"
                );

            if (!button) {
                return;
            }

            const id =
                button.dataset.id;

            const current =
                button.dataset.status;

            const newStatus =
                current === "Active"
                    ? "Inactive"
                    : "Active";

            const result =
                await request(
                    "user_status",
                    {
                        user_id: id,
                        status: newStatus
                    }
                );

            if (result.success) {

                await loadUsers();
                await loadDashboard();

            }

        }
    );


    /* =====================================================
       PHARMACIST ACTIVATE / DEACTIVATE
    ===================================================== */

    document.addEventListener(
        "click",
        async function (event) {

            const button =
                event.target.closest(
                    ".toggle-pharmacist"
                );

            if (!button) {
                return;
            }

            const id =
                button.dataset.id;

            const current =
                button.dataset.status;

            const newStatus =
                current === "Active"
                    ? "Inactive"
                    : "Active";

            const result =
                await request(
                    "pharmacist_status",
                    {
                        user_id: id,
                        status: newStatus
                    }
                );

            if (result.success) {

                await loadPharmacists();
                await loadDashboard();

            }

        }
    );


    /* =====================================================
       DELETE SUPPLIER
    ===================================================== */

    document.addEventListener(
        "click",
        async function (event) {

            const button =
                event.target.closest(
                    ".delete-supplier"
                );

            if (!button) {
                return;
            }

            const id =
                button.dataset.id;

            if (
                !confirm(
                    "Are you sure you want to delete this supplier?"
                )
            ) {
                return;
            }

            const result =
                await request(
                    "delete_supplier",
                    {
                        supplier_id: id
                    }
                );

            if (result.success) {

                alert(
                    "Supplier deleted successfully."
                );

                await loadSuppliers();
                await loadDashboard();

            }

        }
    );


    /* =====================================================
       INLINE ADD FORMS
    ===================================================== */

    function openAddForm(id) {
        const form = document.getElementById(id);
        if (!form) return;
        form.hidden = false;
        form.scrollIntoView({ behavior: "smooth", block: "center" });
        const input = form.querySelector("input");
        if (input) setTimeout(() => input.focus(), 250);
    }

    document.addEventListener("click", function (event) {
        const button = event.target.closest(".add-btn[data-modal]");
        if (!button) return;
        event.preventDefault();
        openAddForm(button.dataset.modal);
    });

    document.addEventListener("click", function (event) {
        const button = event.target.closest("[data-close-form]");
        if (!button) return;
        const form = document.getElementById(button.dataset.closeForm);
        if (form) form.hidden = true;
    });

    async function submitInlineForm(form, action, messageId, reload) {
        const message = document.getElementById(messageId);
        const data = Object.fromEntries(new FormData(form).entries());
        if (data.requires_prescription === undefined) data.requires_prescription = 0;
        if (message) { message.textContent = "Saving..."; message.className = "form-message"; }
        const result = await request(action, data);
        if (message) {
            message.textContent = result.success ? result.message : (result.message || "Unable to save.");
            message.className = "form-message " + (result.success ? "success" : "error");
        }
        if (result.success) {
            form.reset();
            await reload();
        }
    }

    const addUserForm = document.getElementById("addUserForm");
    if (addUserForm) addUserForm.addEventListener("submit", async function (event) {
        event.preventDefault();
        await submitInlineForm(this, "add_user", "addUserMessage", async () => { await loadUsers(); await loadDashboard(); });
    });

    const addPharmacistForm = document.getElementById("addPharmacistForm");
    if (addPharmacistForm) addPharmacistForm.addEventListener("submit", async function (event) {
        event.preventDefault();
        await submitInlineForm(this, "add_pharmacist", "addPharmacistMessage", async () => { await loadPharmacists(); await loadDashboard(); });
    });

    const addSupplierForm = document.getElementById("addSupplierForm");
    if (addSupplierForm) addSupplierForm.addEventListener("submit", async function (event) {
        event.preventDefault();
        await submitInlineForm(this, "add_supplier", "addSupplierMessage", async () => { await loadSuppliers(); await loadDashboard(); });
    });


    /* =====================================================
       EDIT USER
    ===================================================== */

    document.addEventListener(
        "click",
        async function (event) {

            const button =
                event.target.closest(
                    ".edit-user"
                );

            if (!button) {
                return;
            }

            const id =
                button.dataset.id;

            const result =
                await request(
                    "get_user",
                    {
                        user_id: id
                    }
                );

            if (
                !result.success ||
                !result.data
            ) {
                return;
            }

            const user =
                result.data;

            const name =
                prompt(
                    "Full Name:",
                    user.full_name || ""
                );

            if (name === null) return;

            const email =
                prompt(
                    "Email:",
                    user.email || ""
                );

            if (email === null) return;

            const username =
                prompt(
                    "Username:",
                    user.username || ""
                );

            if (username === null) return;

            const phone =
                prompt(
                    "Phone:",
                    user.phone || ""
                );

            if (phone === null) return;

            const address =
                prompt(
                    "Address:",
                    user.address || ""
                );

            if (address === null) return;

            const update =
                await request(
                    "update_user",
                    {
                        user_id: id,
                        full_name: name,
                        email: email,
                        username: username,
                        phone: phone,
                        address: address
                    }
                );

            if (update.success) {

                alert(
                    "User updated successfully."
                );

                await loadUsers();
                await loadDashboard();

            }

        }
    );


    /* =====================================================
       EDIT PHARMACIST
    ===================================================== */

    document.addEventListener(
        "click",
        async function (event) {

            const button =
                event.target.closest(
                    ".edit-pharmacist"
                );

            if (!button) {
                return;
            }

            const id =
                button.dataset.id;

            const result =
                await request(
                    "get_pharmacist",
                    {
                        user_id: id
                    }
                );

            if (
                !result.success ||
                !result.data
            ) {
                return;
            }

            const pharmacist =
                result.data;

            const name =
                prompt(
                    "Full Name:",
                    pharmacist.full_name || ""
                );

            if (name === null) return;

            const email =
                prompt(
                    "Email:",
                    pharmacist.email || ""
                );

            if (email === null) return;

            const username =
                prompt(
                    "Username:",
                    pharmacist.username || ""
                );

            if (username === null) return;

            const phone =
                prompt(
                    "Phone:",
                    pharmacist.phone || ""
                );

            if (phone === null) return;

            const address =
                prompt(
                    "Address:",
                    pharmacist.address || ""
                );

            if (address === null) return;

            const update =
                await request(
                    "update_pharmacist",
                    {
                        user_id: id,
                        full_name: name,
                        email: email,
                        username: username,
                        phone: phone,
                        address: address
                    }
                );

            if (update.success) {

                alert(
                    "Pharmacist updated successfully."
                );

                await loadPharmacists();
                await loadDashboard();

            }

        }
    );


    /* =====================================================
       EDIT SUPPLIER
    ===================================================== */

    document.addEventListener(
        "click",
        async function (event) {

            const button =
                event.target.closest(
                    ".edit-supplier"
                );

            if (!button) {
                return;
            }

            const id =
                button.dataset.id;

            const result =
                await request(
                    "get_supplier",
                    {
                        supplier_id: id
                    }
                );

            if (
                !result.success ||
                !result.data
            ) {
                return;
            }

            const supplier =
                result.data;

            const name =
                prompt(
                    "Supplier Name:",
                    supplier.supplier_name || ""
                );

            if (name === null) return;

            const contact =
                prompt(
                    "Contact Person:",
                    supplier.contact_person || ""
                );

            if (contact === null) return;

            const phone =
                prompt(
                    "Phone:",
                    supplier.phone || ""
                );

            if (phone === null) return;

            const email =
                prompt(
                    "Email:",
                    supplier.email || ""
                );

            if (email === null) return;

            const address =
                prompt(
                    "Address:",
                    supplier.address || ""
                );

            if (address === null) return;

            const update =
                await request(
                    "update_supplier",
                    {
                        supplier_id: id,
                        supplier_name: name,
                        contact_person: contact,
                        phone: phone,
                        email: email,
                        address: address
                    }
                );

            if (update.success) {

                alert(
                    "Supplier updated successfully."
                );

                await loadSuppliers();
                await loadDashboard();

            }

        }
    );


    /* =====================================================
       SEARCH
    ===================================================== */

    setupSearch(
        ".user-search-input",
        ".user-table tbody"
    );

    setupSearch(
        ".pharmacist-search-input",
        ".pharmacist-table tbody"
    );

    setupSearch(
        ".supplier-search-input",
        ".supplier-table tbody"
    );


    function setupSearch(
        inputSelector,
        tbodySelector
    ) {

        const input =
            document.querySelector(
                inputSelector
            );

        if (!input) {
            return;
        }

        input.addEventListener(
            "input",
            function () {

                const keyword =
                    input.value
                        .trim()
                        .toLowerCase();

                const rows =
                    document.querySelectorAll(
                        `${tbodySelector} tr`
                    );

                rows.forEach(
                    function (row) {

                        const text =
                            row.textContent
                                .toLowerCase();

                        row.style.display =
                            text.includes(keyword)
                                ? ""
                                : "none";

                    }
                );

            }
        );

    }


    /* =====================================================
       LOGOUT
    ===================================================== */

    document.querySelectorAll(
        ".logout-link, .header-link"
    ).forEach(function (link) {

        if (
            link.textContent
                .trim()
                .toLowerCase()
                .includes("logout")
        ) {

            link.addEventListener(
                "click",
                async function (event) {

                    event.preventDefault();

                    await request("logout");

                    window.location.href =
                        "login.php";

                }
            );

        }

    });


    /* =====================================================
       HELPER - ESCAPE HTML
    ===================================================== */

    function escapeHTML(value) {

        const div =
            document.createElement("div");

        div.textContent =
            value ?? "";

        return div.innerHTML;

    }


    /* =====================================================
       HELPER - DATE TIME
    ===================================================== */

    function formatDateTime(value) {

        if (!value) {
            return "N/A";
        }

        const parts =
            String(value).split(" ");

        if (parts.length >= 2) {

            return (
                formatDate(parts[0]) +
                " " +
                parts[1]
            );

        }

        return formatDate(
            String(value)
        );

    }


    /* =====================================================
       HELPER - DATE
    ===================================================== */

    function formatDate(value) {

        if (!value) {
            return "N/A";
        }

        const parts =
            String(value).split("-");

        if (parts.length === 3) {

            return (
                parts[2] +
                "." +
                parts[1] +
                "." +
                parts[0]
            );

        }

        return value;

    }


    /* =====================================================
       HELPER - STATUS CLASS
    ===================================================== */

    function getStatusClass(status) {

        status =
            String(status)
                .toLowerCase();

        if (
            status === "approved" ||
            status === "active"
        ) {
            return "active";
        }

        if (
            status === "rejected" ||
            status === "inactive"
        ) {
            return "inactive";
        }

        return "pending";

    }


    /* =====================================================
       INITIAL LOAD
    ===================================================== */

    loadDashboard();

    loadUsers();

    loadPharmacists();

    loadSuppliers();

    loadStockApprovals();

    loadNotifications();

});