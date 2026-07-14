import './bootstrap';

window.lastClickCoords = { x: window.innerWidth / 2, y: window.innerHeight / 2 };

document.addEventListener('click', (e) => {
    // Odrobinę nad kursorem myszy zgodnie z uwagą (Y - 10)
    window.lastClickCoords = { 
        x: e.clientX, 
        y: e.clientY - 10 
    };
});
