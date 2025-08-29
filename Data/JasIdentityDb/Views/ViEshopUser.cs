using Microsoft.EntityFrameworkCore;

namespace Jas.Data.JasIdentityDb;

[Keyless]
public class ViEshopUser
{
    public string? Jmeno { get; set; }
    public string? Prijmeni { get; set; }
    public string? ObchodniJmeno { get; set; }
    public string? ZkracenyNazev { get; set; }
    public string? Mesto { get; set; }
    public string? Ico { get; set; }
    public string? Pc { get; set; }
    public int? Skp { get; set; }
    public string? Login { get; set; }
    public string? Heslo { get; set; }
    public string? EmailOdberatele { get; set; }
    public string? EmailObchodnika { get; set; }
    public bool? Objednavky { get; set; }
    public bool? Sklad { get; set; }
    public bool? Paleta { get; set; }
    public bool? NedodanePolozky { get; set; }
    public bool? SkladVlastniPobocky { get; set; }
    public bool? SkladVsech { get; set; }
    public string? RabSkp { get; set; }
    public string? UliceACislo { get; set; }
    public string? Psc { get; set; }
    public string? Telefon { get; set; }
    public int? K2IdZakaznika { get; set; }
    public int? K2IdCenoveSkupiny { get; set; }
}
