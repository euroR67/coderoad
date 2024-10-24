function showSuccessMessage(message){
    return Toastify({
        text: message, //"URL GitHub mise à jour avec succès !",
        duration: 3000,
        destination: "https://github.com/apvarun/toastify-js",
        newWindow: true,
        close: true,
        gravity: "top", // `top` or `bottom`
        position: "left", // `left`, `center` or `right`
        stopOnFocus: true, // Prevents dismissing of toast on hover
        style: {
            background: "#00b447",
        },
        onClick: function(){} // Callback after click
    }).showToast();
}

function showErrorMessage(message){
    return Toastify({
        text: message,
        duration: 3000,
        destination: "https://github.com/apvarun/toastify-js",
        newWindow: true,
        close: true,
        gravity: "top", // `top` or `bottom`
        position: "left", // `left`, `center` or `right`
        stopOnFocus: true, // Prevents dismissing of toast on hover
        style: {
            background: "#ff0000",
        },
        onClick: function(){} // Callback after click
    }).showToast();
}