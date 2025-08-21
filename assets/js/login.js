const login = () => {

    let usuarioRegistrado = document.querySelector('#usuarioRegistrado').value;
    let contraseña = document.querySelector('#contraseña').value;

    $.ajax({
        url: 'http://app.xl.com.ar:6002/api/login',
        method: 'POST',
        contentType: "application/json",
        dataType: "json",

        data: JSON.stringify({ 

            username: usuarioRegistrado,
            password: contraseña

        }),

        success: (response) => {

            if (response) {
                localStorage.setItem('token', response.token);
                $.ajax({

                    url: 'validar.php',
                    method: 'POST',
                    data: {
                        username: usuarioRegistrado,
                    },
          
                    success: (response) => {
                        window.location.href = 'index.php';
                    
                    },

                });
                    
            } else {
                alert('Usuario o contraseña incorrectos');
            }
        },
        error : (error) => {
            alert('Usuario o contraseña incorrectos');
        }
    });
    
}