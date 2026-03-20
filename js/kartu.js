const params = new URLSearchParams(window.location.search);

document.getElementById("nama").innerText = params.get("nama");
document.getElementById("noDaftar").innerText = params.get("no");
document.getElementById("nisn").innerText = params.get("nisn");
document.getElementById("jurusan").innerText = params.get("jurusan");
