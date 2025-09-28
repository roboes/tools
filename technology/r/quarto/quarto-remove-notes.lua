-- R Quarto Speaker Notes Remover
-- Last update: 2025-09-05


function Div (el)
  if el.classes:includes("notes") then
    return {}
  else
    return el
  end
end
