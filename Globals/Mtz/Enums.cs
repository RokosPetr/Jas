namespace Jas.Globals.Mtz.Enums
{

    public enum OrderStates : int
    {
        NotDeliveredItem = -1,
        InCart = 0,
        Received = 1,
        InProgress = 2,
        ToSend = 3,
        Processed = 4,
        Cancelled = 5,
        Ordered = 6,
        ProcessedCancelled = 7
    }

}
